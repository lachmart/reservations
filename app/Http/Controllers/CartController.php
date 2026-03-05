<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Reservation;
use App\Models\ReservationItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReservationCreated;

class CartController extends Controller
{
    protected function getCart(Request $request): array
    {
        $cart = $request->session()->get('cart', [
            'start_date' => null,
            'end_date' => null,
            'items' => [], // [ product_id => amount ] (expanded, includes bundles)
            'bundles' => [], // [ bundle_product_id => amount ] for display and editing bundles
        ]);
        // Ensure keys exist after older sessions
        $cart['items'] = $cart['items'] ?? [];
        $cart['bundles'] = $cart['bundles'] ?? [];
        return $cart;
    }

    /**
     * Compute how many units of the given product are contributed by all bundles currently in the cart.
     */
    protected function bundleContributionForProduct(Product $product, array $cart): int
    {
        $contrib = 0;
        if (empty($cart['bundles'])) {
            return 0;
        }
        $bundleIds = array_keys($cart['bundles']);
        $bundles = Product::whereIn('id', $bundleIds)->get();
        foreach ($bundles as $bundle) {
            $bundleQty = (int) ($cart['bundles'][$bundle->id] ?? 0);
            if ($bundleQty <= 0) continue;
            foreach ($bundle->bundleItems as $child) {
                if ((int) $child->id === (int) $product->id) {
                    $contrib += ((int) $child->pivot->amount) * $bundleQty;
                }
            }
        }
        return $contrib;
    }

    protected function putCart(Request $request, array $cart): void
    {
        $request->session()->put('cart', $cart);
    }

    public function index(Request $request): View
    {
        $cart = $this->getCart($request);
        $displayRows = [];

        // Resolve all products needed for items and bundles
        $productIds = array_keys($cart['items'] ?: []);
        $bundleIds = array_keys($cart['bundles'] ?: []);
        $allIds = array_unique(array_merge($productIds, $bundleIds));
        $products = Product::whereIn('id', $allIds)->get()->keyBy('id');

        $days = null;
        if (!empty($cart['start_date']) && !empty($cart['end_date'])) {
            try {
                $start = \Carbon\Carbon::parse($cart['start_date']);
                $end = \Carbon\Carbon::parse($cart['end_date']);
                $minutes = $start->diffInMinutes($end);
                $days = max(1, (int) ceil($minutes / (60 * 24)));
            } catch (\Throwable $e) {
                $days = null; // in case of invalid date in session
            }
        }

        // Compute contributions from bundles to child product quantities
        $bundleContrib = [];
        foreach ($cart['bundles'] as $bundleId => $bundleQty) {
            $bundle = $products[$bundleId] ?? null;
            if (!$bundle) continue;
            foreach ($bundle->bundleItems as $child) {
                $bundleContrib[$child->id] = ($bundleContrib[$child->id] ?? 0) + ($child->pivot->amount * (int)$bundleQty);
            }
        }

        $total = 0.0;

        // First add bundle rows
        foreach ($cart['bundles'] as $bundleId => $bundleQty) {
            $bundle = $products[$bundleId] ?? null;
            if (!$bundle) continue;

            // Use bundle's own price per day (can differ from sum of components)
            $bundlePricePerDay = (float) ($bundle->price_per_day ?? 0);

            // Compute available bundles in the selected range if dates are set
            $availableBundles = null;
            if (!is_null($days)) {
                $possibleCounts = [];
                foreach ($bundle->bundleItems as $child) {
                    $availableChild = $child->availableForRange($cart['start_date'], $cart['end_date']);
                    // Child quantity required per single bundle
                    $perBundle = max(1, (int) $child->pivot->amount);
                    // IMPORTANT: Do not subtract items already in the cart when showing availability.
                    $remainingForNewBundles = intdiv(max(0, $availableChild), $perBundle);
                    $possibleCounts[] = $remainingForNewBundles;
                }
                $availableBundles = !empty($possibleCounts) ? min($possibleCounts) : 0;
            }

            $subtotal = !is_null($days) ? $bundlePricePerDay * (int) $bundleQty * $days : null;
            if (!is_null($subtotal)) $total += $subtotal;

            $displayRows[] = [
                'type' => 'bundle',
                'product' => $bundle,
                'amount' => (int) $bundleQty,
                'available' => $availableBundles,
                'price_per_day' => $bundlePricePerDay,
                'subtotal' => $subtotal,
                'components' => $bundle->bundleItems,
            ];
        }

        // Then add standalone product rows (excluding quantities contributed by bundles)
        foreach ($cart['items'] as $productId => $amount) {
            $product = $products[$productId] ?? null;
            if (!$product) continue;
            $contrib = (int) ($bundleContrib[$productId] ?? 0);
            $standaloneAmount = (int) $amount - $contrib;
            if ($standaloneAmount <= 0) continue; // fully covered by bundles

            $available = (!is_null($days)) ? $product->availableForRange($cart['start_date'], $cart['end_date']) : null;
            $pricePerDay = (float) ($product->price_per_day ?? 0);
            $subtotal = (!is_null($days)) ? $pricePerDay * $standaloneAmount * $days : null;
            if (!is_null($subtotal)) $total += $subtotal;

            $displayRows[] = [
                'type' => 'product',
                'product' => $product,
                'amount' => $standaloneAmount,
                'available' => $available,
                'price_per_day' => $pricePerDay,
                'subtotal' => $subtotal,
            ];
        }

        $total = (!is_null($days)) ? $total : null;

        return view('cart.index', [
            'items' => $displayRows,
            'start' => $cart['start_date'],
            'end' => $cart['end_date'],
            'days' => $days,
            'total' => $total,
        ]);
    }

    public function setDates(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);
        $cart = $this->getCart($request);
        $cart['start_date'] = $data['start_date'];
        $cart['end_date'] = $data['end_date'];
        $this->putCart($request, $cart);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'ok', 'start_date' => $cart['start_date'], 'end_date' => $cart['end_date']]);
        }
        return back()->with('success', 'Term updated.');
    }

    public function add(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        $cart = $this->getCart($request);

        $product = Product::findOrFail($data['product_id']);
        $amount = (int) $data['amount'];

        $isBundle = method_exists($product, 'isBundle') && $product->isBundle();
        $currentQty = $isBundle ? (int) Arr::get($cart, 'bundles.' . $product->id, 0) : (int) Arr::get($cart, 'items.' . $product->id, 0);
        $available = $product->available_quantity ?? $product->stock;

        // If it's a bundle, we skip basic validation because stock on bundle might not be used correctly
        if (!$isBundle && $available !== null && ($currentQty + $amount) > $available) {
            $message = "Cannot add – available quantity is {$available} pcs, you already have {$currentQty} pcs in cart.";
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }
            return back()->withErrors(['amount' => $message])->withInput();
        }

        if ($isBundle) {
            // Adding a bundle: validate each component against availability and expand items
            if (!empty($cart['start_date']) && !empty($cart['end_date'])) {
                foreach ($product->bundleItems as $child) {
                    $available = $child->availableForRange($cart['start_date'], $cart['end_date']);
                    $existing = (int) Arr::get($cart['items'], $child->id, 0);
                    $required = (int) $child->pivot->amount * $amount;
                    if (($existing + $required) > $available) {
                        if ($request->expectsJson()) {
                            return response()->json(['status' => 'error', 'message' => 'Requested bundle quantity is not available for the selected term.'], 422);
                        }
                        return back()->withErrors(['amount' => 'Requested bundle quantity is not available for the selected term.'])->withInput();
                    }
                }
            }
            // Increase bundle count for display
            $cart['bundles'][$product->id] = (int) Arr::get($cart['bundles'], $product->id, 0) + $amount;
            // Expand to underlying items
            foreach ($product->bundleItems as $child) {
                $cart['items'][$child->id] = (int) Arr::get($cart['items'], $child->id, 0) + ((int) $child->pivot->amount * $amount);
            }
        } else {
            // Simple product
            if (!empty($cart['start_date']) && !empty($cart['end_date'])) {
                $available = $product->availableForRange($cart['start_date'], $cart['end_date']);
                $existing = (int) Arr::get($cart['items'], $product->id, 0);
                if (($existing + $amount) > $available) {
                    if ($request->expectsJson()) {
                        return response()->json(['status' => 'error', 'message' => 'Requested quantity is not available for the selected term.'], 422);
                    }
                    return back()->withErrors(['amount' => 'Requested quantity is not available for the selected term.'])->withInput();
                }
            }
            $cart['items'][$product->id] = (int) Arr::get($cart['items'], $product->id, 0) + $amount;
        }

        $this->putCart($request, $cart);

        // JSON/AJAX path: do not redirect
        if ($request->expectsJson()) {
            $totalCount = (int) array_sum((array) ($cart['items'] ?? []));
            $perProductCount = (method_exists($product, 'isBundle') && $product->isBundle())
                ? (int) Arr::get($cart, 'bundles.' . $product->id, 0)
                : (int) Arr::get($cart, 'items.' . $product->id, 0);
            return response()->json([
                'status' => 'ok',
                'message' => 'Item was added to cart.',
                'cart_count' => $totalCount,
                'product_id' => (int) $product->id,
                'product_is_bundle' => method_exists($product, 'isBundle') ? (bool) $product->isBundle() : false,
                'product_count' => $perProductCount,
            ]);
        }

        // Fallback: redirect for non-AJAX
        return redirect()->back()->with('success', 'Item was added to cart.');
    }

    public function update(Request $request, Product $product): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
        ]);
        $cart = $this->getCart($request);

        $amount = (int) $data['amount'];
        $isBundle = method_exists($product, 'isBundle') && $product->isBundle();
        $available = $product->available_quantity ?? $product->stock;

        if (!$isBundle && $available !== null && $amount > $available) {
            $message = "Maximum quantity is {$available} pcs.";
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }
            return back()->withErrors(['amount' => $message])->withInput();
        }

        if (!isset($cart['items'][$product->id]) && !$isBundle && !isset($cart['bundles'][$product->id])) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Item is not in cart.'], 404);
            }
            return back()->withErrors(['item' => 'Item is not in cart.']);
        }
        // The provided amount is intended to update only the standalone quantity (excluding bundle contributions)
        $newStandalone = (int) $data['amount'];
        $bundleContrib = $this->bundleContributionForProduct($product, $cart);
        $desiredTotal = $bundleContrib + $newStandalone;

        $message = 'Quantity was updated.';
        if (!empty($cart['start_date']) && !empty($cart['end_date'])) {
            $available = $product->availableForRange($cart['start_date'], $cart['end_date']);
            if ($desiredTotal > $available) {
                // clamp to maximum available standalone amount
                $maxStandalone = max(0, (int) $available - (int) $bundleContrib);
                $newStandalone = $maxStandalone;
                $desiredTotal = $bundleContrib + $newStandalone;
                $message = 'Requested quantity was not available, so it was adjusted to maximum available.';
            }
        }
        if ($desiredTotal > 0) {
            $cart['items'][$product->id] = $desiredTotal;
        } else {
            unset($cart['items'][$product->id]);
        }
        $this->putCart($request, $cart);

        if ($request->expectsJson()) {
            $totalCount = (int) array_sum((array) ($cart['items'] ?? []));
            return response()->json([
                'status' => 'ok',
                'message' => $message,
                'product_id' => (int) $product->id,
                'product_is_bundle' => false,
                // amounts for UI
                'standalone_amount' => (int) $newStandalone,
                'total_amount' => (int) ($cart['items'][$product->id] ?? 0),
                'cart_count' => $totalCount,
            ]);
        }

        return back()->with('success', $message);
    }

    public function updateBundle(Request $request, Product $product): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        // $product is the bundle product
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
        ]);
        $cart = $this->getCart($request);
        if (!isset($cart['bundles'][$product->id])) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Bundle is not in cart.'], 404);
            }
            return back()->withErrors(['item' => 'Bundle is not in cart.']);
        }
        $current = (int) $cart['bundles'][$product->id];
        $requestedAmount = (int) $data['amount'];
        $message = 'Bundle quantity was updated.';

        // Clamp to max available bundles when dates are set
        $newAmount = $requestedAmount;
        if (!empty($cart['start_date']) && !empty($cart['end_date'])) {
            $maxNew = PHP_INT_MAX;
            foreach ($product->bundleItems as $child) {
                $available = $child->availableForRange($cart['start_date'], $cart['end_date']);
                $existing = (int) Arr::get($cart['items'], $child->id, 0);
                $perBundle = max(1, (int) $child->pivot->amount);
                // current items already include current bundle contribution; compute bound for newAmount
                $maxForChild = $current + intdiv(max(0, $available - $existing), $perBundle);
                $maxNew = min($maxNew, $maxForChild);
            }
            if (is_infinite($maxNew)) { $maxNew = $current; }
            if ($newAmount > $maxNew) {
                $newAmount = (int) $maxNew;
                $message = 'Requested bundle quantity was not available, so it was adjusted to maximum available.';
            }
        }

        $delta = $newAmount - $current; // positive to add more, negative to reduce
        if ($delta !== 0) {
            // Apply delta to items
            foreach ($product->bundleItems as $child) {
                $cart['items'][$child->id] = (int) Arr::get($cart['items'], $child->id, 0) + ((int) $child->pivot->amount * $delta);
                if ($cart['items'][$child->id] <= 0) {
                    unset($cart['items'][$child->id]);
                }
            }
        }
        // Set new bundle amount
        if ($newAmount > 0) {
            $cart['bundles'][$product->id] = $newAmount;
        } else {
            unset($cart['bundles'][$product->id]);
        }
        $this->putCart($request, $cart);

        if ($request->expectsJson()) {
            $totalCount = (int) array_sum((array) ($cart['items'] ?? []));
            return response()->json([
                'status' => 'ok',
                'message' => $message,
                'product_id' => (int) $product->id,
                'product_is_bundle' => true,
                'bundle_amount' => (int) ($cart['bundles'][$product->id] ?? 0),
                'cart_count' => $totalCount,
            ]);
        }

        return back()->with('success', $message);
    }

    public function remove(Request $request, Product $product): RedirectResponse
    {
        $cart = $this->getCart($request);
        // Only remove standalone quantity; keep quantities contributed by bundles
        $bundleContrib = $this->bundleContributionForProduct($product, $cart);
        if ($bundleContrib > 0) {
            $cart['items'][$product->id] = $bundleContrib;
        } else {
            unset($cart['items'][$product->id]);
        }
        $this->putCart($request, $cart);
        return back()->with('success', 'Item was removed from cart.');
    }

    public function removeBundle(Request $request, Product $product): RedirectResponse
    {
        // $product is the bundle product
        $cart = $this->getCart($request);
        if (!isset($cart['bundles'][$product->id])) {
            return back()->withErrors(['item' => 'Bundle is not in cart.']);
        }
        $bundleQty = (int) $cart['bundles'][$product->id];
        // Reduce child items accordingly
        foreach ($product->bundleItems as $child) {
            $reduce = (int) $child->pivot->amount * $bundleQty;
            $current = (int) Arr::get($cart['items'], $child->id, 0);
            $new = $current - $reduce;
            if ($new > 0) {
                $cart['items'][$child->id] = $new;
            } else {
                unset($cart['items'][$child->id]);
            }
        }
        // Remove bundle entry
        unset($cart['bundles'][$product->id]);
        $this->putCart($request, $cart);
        return back()->with('success', 'Bundle was removed from cart.');
    }

    public function clear(Request $request): RedirectResponse
    {
        $request->session()->forget('cart');
        return back()->with('success', 'Cart was cleared.');
    }

    public function thankyou(): View
    {
        return view('cart.thankyou');
    }

    public function checkout(Request $request): RedirectResponse
    {
        $cart = $this->getCart($request);
        if (empty($cart['items'])) {
            return back()->withErrors(['cart' => 'Cart is empty.']);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        if (empty($cart['start_date']) || empty($cart['end_date'])) {
            return back()->withErrors(['dates' => 'Please set the reservation term first.']);
        }

        // Validate dates order (end must be after start)
        try {
            $start = \Carbon\Carbon::parse($cart['start_date']);
            $end = \Carbon\Carbon::parse($cart['end_date']);
            if ($end->lessThanOrEqualTo($start)) {
                return back()->withErrors(['dates' => 'End date must be after start date.'])->withInput();
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['dates' => 'Invalid reservation term.'])->withInput();
        }

        // Validate availability for all items atomically and create reservation
        try {
            DB::beginTransaction();

            // Re-check availability
            foreach ($cart['items'] as $productId => $amount) {
                $product = Product::findOrFail($productId);
                $available = $product->availableForRange($cart['start_date'], $cart['end_date']);
                if ($amount > $available) {
                    DB::rollBack();
                    return back()->withErrors(['cart' => 'Some items are not available in requested quantity.'])->withInput();
                }
            }

            // Calculate total price for the reservation
            try {
                $minutes = $start->diffInMinutes($end);
                $days = max(1, (int) ceil($minutes / (60 * 24)));
            } catch (\Throwable $e) {
                DB::rollBack();
                return back()->withErrors(['dates' => 'Invalid reservation term.'])->withInput();
            }

            // Compute total using bundle pricing where applicable, and standalone items excluding bundle contributions
            $totalPrice = 0.0;

            // Resolve products for items and bundles
            $productIds = array_keys($cart['items'] ?: []);
            $bundleIds = array_keys($cart['bundles'] ?: []);
            $allIds = array_unique(array_merge($productIds, $bundleIds));
            $products = Product::whereIn('id', $allIds)->get()->keyBy('id');

            // Sum bundle prices
            foreach ($cart['bundles'] as $bundleId => $bundleQty) {
                $bundle = $products[$bundleId] ?? null;
                if (!$bundle) continue;
                $bundlePricePerDay = (float) ($bundle->price_per_day ?? 0);
                $totalPrice += $bundlePricePerDay * (int) $bundleQty * $days;
            }

            // Compute bundle contributions map
            $bundleContrib = [];
            foreach ($cart['bundles'] as $bundleId => $bundleQty) {
                $bundle = $products[$bundleId] ?? null;
                if (!$bundle) continue;
                foreach ($bundle->bundleItems as $child) {
                    $bundleContrib[$child->id] = ($bundleContrib[$child->id] ?? 0) + ((int) $child->pivot->amount * (int) $bundleQty);
                }
            }

            // Add standalone items (excluding quantities covered by bundles)
            foreach ($cart['items'] as $productId => $amount) {
                $product = $products[$productId] ?? null;
                if (!$product) continue;
                $standaloneAmount = (int) $amount - (int) ($bundleContrib[$productId] ?? 0);
                if ($standaloneAmount <= 0) continue;
                $totalPrice += ((float) ($product->price_per_day ?? 0)) * $standaloneAmount * $days;
            }

            $reservation = Reservation::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? '',
                'note' => $data['note'] ?? '',
                'start_date' => $cart['start_date'],
                'end_date' => $cart['end_date'],
                'price' => $totalPrice,
                'is_active' => true,
                'is_paid' => false,
            ]);

            foreach ($cart['items'] as $productId => $amount) {
                ReservationItem::create([
                    'reservation_id' => $reservation->id,
                    'product_id' => $productId,
                    'amount' => (int) $amount,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['server' => 'An error occurred while creating the reservation.'])->withInput();
        }

        // Clear cart
        $request->session()->forget('cart');

        // Send confirmation email (non-blocking try/catch to avoid breaking UX)
        try {
            Mail::to($reservation->email)->send(new ReservationCreated($reservation));
        } catch (\Throwable $e) {
            // Optionally log error; keeping silent to not disrupt the flow
            \Log::warning('Reservation confirmation email failed: ' . $e->getMessage(), [
                'reservation_id' => $reservation->id ?? null,
            ]);
        }

        return redirect()->route('cart.thankyou')->with('success', 'Reservation was created. Thank you!');
    }
}
