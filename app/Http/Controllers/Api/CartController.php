<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Mail\ReservationCreated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class CartController extends Controller
{
    protected function getCart(Request $request): array
    {
        $cart = $request->session()->get('cart', [
            'start_date' => null,
            'end_date' => null,
            'items' => [], // [ product_id => amount ]
            'bundles' => [], // [ bundle_product_id => amount ]
        ]);
        $cart['items'] = $cart['items'] ?? [];
        $cart['bundles'] = $cart['bundles'] ?? [];
        return $cart;
    }

    protected function putCart(Request $request, array $cart): void
    {
        $request->session()->put('cart', $cart);
    }

    public function index(Request $request): JsonResponse
    {
        $cart = $this->getCart($request);

        $productIds = array_keys($cart['items'] ?: []);
        $bundleIds = array_keys($cart['bundles'] ?: []);
        $allIds = array_unique(array_merge($productIds, $bundleIds));
        $products = Product::whereIn('id', $allIds)->with('bundleItems')->get()->keyBy('id');

        $days = null;
        if (!empty($cart['start_date']) && !empty($cart['end_date'])) {
            try {
                $start = Carbon::parse($cart['start_date']);
                $end = Carbon::parse($cart['end_date']);
                $minutes = $start->diffInMinutes($end);
                $days = max(1, (int) ceil($minutes / (60 * 24)));
            } catch (\Throwable $e) {
                $days = null;
            }
        }

        $items = [];
        $total = 0.0;

        foreach ($cart['bundles'] as $id => $qty) {
            $product = $products[$id] ?? null;
            if (!$product) continue;

            $subtotal = $product->price_per_day * $qty * ($days ?: 1);
            $total += $subtotal;

            $items[] = [
                'id' => $id,
                'name' => $product->name,
                'type' => 'bundle',
                'quantity' => $qty,
                'price_per_day' => (float)$product->price_per_day,
                'subtotal' => (float)$subtotal,
            ];
        }

        foreach ($cart['items'] as $id => $qty) {
            $product = $products[$id] ?? null;
            if (!$product) continue;

            $subtotal = $product->price_per_day * $qty * ($days ?: 1);
            $total += $subtotal;

            $items[] = [
                'id' => $id,
                'name' => $product->name,
                'type' => 'product',
                'quantity' => $qty,
                'price_per_day' => (float)$product->price_per_day,
                'subtotal' => (float)$subtotal,
            ];
        }

        return response()->json([
            'start_date' => $cart['start_date'],
            'end_date' => $cart['end_date'],
            'days' => $days,
            'items' => $items,
            'total_price' => (float)$total,
        ]);
    }

    public function setDates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
        ]);

        $cart = $this->getCart($request);
        $cart['start_date'] = $validated['start_date'];
        $cart['end_date'] = $validated['end_date'];
        $this->putCart($request, $cart);

        return response()->json(['message' => 'Dates updated', 'cart' => $cart]);
    }

    public function add(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'amount' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $cart = $this->getCart($request);
        $amount = (int)$validated['amount'];

        $isBundle = $product->isBundle();
        $currentQty = $isBundle ? ($cart['bundles'][$product->id] ?? 0) : ($cart['items'][$product->id] ?? 0);
        $available = $product->available_quantity ?? $product->stock;

        // If dates are set, use more precise availability (especially for bundles)
        if (!empty($cart['start_date']) && !empty($cart['end_date'])) {
            if ($isBundle) {
                foreach ($product->bundleItems as $child) {
                    $childAvailable = $child->availableForRange($cart['start_date'], $cart['end_date']);
                    $childInCart = $cart['items'][$child->id] ?? 0;
                    $needed = $child->pivot->amount * $amount;
                    if (($childInCart + $needed) > $childAvailable) {
                        return response()->json([
                            'message' => "Bundle cannot be added – component '{$child->name}' is not available in requested amount (available {$childAvailable} pcs, already in cart indirectly {$childInCart} pcs)."
                        ], 422);
                    }
                }
            } else {
                $rangeAvailable = $product->availableForRange($cart['start_date'], $cart['end_date']);
                if (($currentQty + $amount) > $rangeAvailable) {
                    return response()->json([
                        'message' => "Cannot add – for selected term there are {$rangeAvailable} pcs available, you already have {$currentQty} pcs in cart."
                    ], 422);
                }
            }
        } else {
            // Without dates, just basic stock validation (if not null)
            if ($available !== null && ($currentQty + $amount) > $available) {
                return response()->json([
                    'message' => "Cannot add – available quantity is {$available} pcs, you already have {$currentQty} pcs in cart."
                ], 422);
            }
        }

        if ($product->isBundle()) {
            $cart['bundles'][$product->id] = ($cart['bundles'][$product->id] ?? 0) + $amount;
        } else {
            $cart['items'][$product->id] = ($cart['items'][$product->id] ?? 0) + $amount;
        }

        $this->putCart($request, $cart);

        return response()->json(['message' => 'Product added to cart', 'cart' => $cart]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:0',
        ]);

        $cart = $this->getCart($request);
        $amount = (int)$validated['amount'];

        $isBundle = $product->isBundle();
        $available = $product->available_quantity ?? $product->stock;

        if (!empty($cart['start_date']) && !empty($cart['end_date'])) {
            if ($isBundle) {
                // For bundle, we must simulate how many items would be in cart after change
                $oldAmount = $cart['bundles'][$product->id] ?? 0;
                $diff = $amount - $oldAmount;
                foreach ($product->bundleItems as $child) {
                    $childAvailable = $child->availableForRange($cart['start_date'], $cart['end_date']);
                    $childInCart = $cart['items'][$child->id] ?? 0;
                    $neededDiff = $child->pivot->amount * $diff;
                    if (($childInCart + $neededDiff) > $childAvailable) {
                        return response()->json([
                            'message' => "Bundle cannot be updated – component '{$child->name}' would exceed availability (available {$childAvailable} pcs)."
                        ], 422);
                    }
                }
            } else {
                $rangeAvailable = $product->availableForRange($cart['start_date'], $cart['end_date']);
                if ($amount > $rangeAvailable) {
                    return response()->json([
                        'message' => "Maximum quantity for selected term is {$rangeAvailable} pcs."
                    ], 422);
                }
            }
        } else {
            if ($available !== null && $amount > $available) {
                return response()->json([
                    'message' => "Maximum quantity is {$available} pcs."
                ], 422);
            }
        }

        if ($product->isBundle()) {
            if ($amount <= 0) {
                unset($cart['bundles'][$product->id]);
            } else {
                $cart['bundles'][$product->id] = $amount;
            }
        } else {
            if ($amount <= 0) {
                unset($cart['items'][$product->id]);
            } else {
                $cart['items'][$product->id] = $amount;
            }
        }

        $this->putCart($request, $cart);

        return response()->json(['message' => 'Cart updated', 'cart' => $cart]);
    }

    public function remove(Request $request, Product $product): JsonResponse
    {
        $cart = $this->getCart($request);

        unset($cart['items'][$product->id]);
        unset($cart['bundles'][$product->id]);

        $this->putCart($request, $cart);

        return response()->json(['message' => 'Product removed from cart', 'cart' => $cart]);
    }

    public function clear(Request $request): JsonResponse
    {
        $request->session()->forget('cart');
        return response()->json(['message' => 'Cart cleared']);
    }

    public function checkout(Request $request): JsonResponse
    {
        $cart = $this->getCart($request);

        if (empty($cart['items']) && empty($cart['bundles'])) {
            return response()->json(['error' => 'Cart is empty'], 422);
        }

        if (empty($cart['start_date']) || empty($cart['end_date'])) {
            return response()->json(['error' => 'Dates not set'], 422);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'note' => 'nullable|string',
        ]);

        // Calculate total price (re-calculate to be safe)
        $productIds = array_keys($cart['items'] ?: []);
        $bundleIds = array_keys($cart['bundles'] ?: []);
        $allIds = array_unique(array_merge($productIds, $bundleIds));
        $products = Product::whereIn('id', $allIds)->get()->keyBy('id');

        $start = Carbon::parse($cart['start_date']);
        $end = Carbon::parse($cart['end_date']);
        $days = max(1, (int) ceil($start->diffInMinutes($end) / (60 * 24)));

        $totalPrice = 0;
        foreach ($cart['bundles'] as $id => $qty) {
            $totalPrice += ($products[$id]->price_per_day ?? 0) * $qty * $days;
        }
        foreach ($cart['items'] as $id => $qty) {
            $totalPrice += ($products[$id]->price_per_day ?? 0) * $qty * $days;
        }

        try {
            DB::beginTransaction();

            $reservation = Reservation::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'note' => $validated['note'],
                'start_date' => $cart['start_date'],
                'end_date' => $cart['end_date'],
                'price' => $totalPrice,
                'is_active' => true,
                'is_paid' => false,
            ]);

            // Add bundle items expanded
            foreach ($cart['bundles'] as $bundleId => $bundleQty) {
                $bundle = $products[$bundleId];
                foreach ($bundle->bundleItems as $child) {
                    $amount = $child->pivot->amount * $bundleQty;
                    $this->addOrUpdateItem($reservation, $child->id, $amount);
                }
            }

            // Add regular items
            foreach ($cart['items'] as $productId => $qty) {
                $this->addOrUpdateItem($reservation, $productId, $qty);
            }

            DB::commit();

            Mail::to($reservation->email)->send(new ReservationCreated($reservation));

            $request->session()->forget('cart');

            return response()->json([
                'message' => 'Reservation created successfully',
                'reservation_id' => $reservation->id
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create reservation: ' . $e->getMessage()], 500);
        }
    }

    protected function addOrUpdateItem(Reservation $reservation, $productId, $amount): void
    {
        $item = $reservation->reservationItems()->where('product_id', $productId)->first();
        if ($item) {
            $item->increment('amount', $amount);
        } else {
            $reservation->reservationItems()->create([
                'product_id' => $productId,
                'amount' => $amount,
            ]);
        }
    }
}
