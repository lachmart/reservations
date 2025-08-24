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
                    // Existing quantity of this child already in cart (both direct and from other bundles)
                    $existingChild = (int) Arr::get($cart['items'], $child->id, 0);
                    // Child quantity required per single bundle
                    $perBundle = (int) $child->pivot->amount;
                    // Max additional bundles possible given current existing reservations in cart
                    $remainingForNewBundles = intdiv(max(0, $availableChild - $existingChild), max(1, $perBundle));
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
        return back()->with('success', 'Termín byl nastaven.');
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

        if (method_exists($product, 'isBundle') && $product->isBundle()) {
            // Adding a bundle: validate each component against availability and expand items
            if (!empty($cart['start_date']) && !empty($cart['end_date'])) {
                foreach ($product->bundleItems as $child) {
                    $available = $child->availableForRange($cart['start_date'], $cart['end_date']);
                    $existing = (int) Arr::get($cart['items'], $child->id, 0);
                    $required = (int) $child->pivot->amount * $amount;
                    if (($existing + $required) > $available) {
                        if ($request->expectsJson()) {
                            return response()->json(['status' => 'error', 'message' => 'Požadované množství bundlu není pro zadaný termín dostupné.'], 422);
                        }
                        return back()->withErrors(['amount' => 'Požadované množství bundlu není pro zadaný termín dostupné.'])->withInput();
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
                        return response()->json(['status' => 'error', 'message' => 'Požadované množství není pro zadaný termín dostupné.'], 422);
                    }
                    return back()->withErrors(['amount' => 'Požadované množství není pro zadaný termín dostupné.'])->withInput();
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
                'message' => 'Položka byla přidána do košíku.',
                'cart_count' => $totalCount,
                'product_id' => (int) $product->id,
                'product_is_bundle' => method_exists($product, 'isBundle') ? (bool) $product->isBundle() : false,
                'product_count' => $perProductCount,
            ]);
        }

        // Fallback: redirect for non-AJAX
        return redirect()->back()->with('success', 'Položka byla přidána do košíku.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
        ]);
        $cart = $this->getCart($request);
        if (!isset($cart['items'][$product->id])) {
            return back()->withErrors(['item' => 'Položka není v košíku.']);
        }
        // The provided amount is intended to update only the standalone quantity (excluding bundle contributions)
        $newStandalone = (int) $data['amount'];
        $bundleContrib = $this->bundleContributionForProduct($product, $cart);
        $desiredTotal = $bundleContrib + $newStandalone;
        if (!empty($cart['start_date']) && !empty($cart['end_date'])) {
            $available = $product->availableForRange($cart['start_date'], $cart['end_date']);
            if ($desiredTotal > $available) {
                return back()->withErrors(['amount' => 'Požadované množství není pro zadaný termín dostupné.'])->withInput();
            }
        }
        if ($desiredTotal > 0) {
            $cart['items'][$product->id] = $desiredTotal;
        } else {
            unset($cart['items'][$product->id]);
        }
        $this->putCart($request, $cart);
        return back()->with('success', 'Množství bylo aktualizováno.');
    }

    public function updateBundle(Request $request, Product $product): RedirectResponse
    {
        // $product is the bundle product
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
        ]);
        $cart = $this->getCart($request);
        if (!isset($cart['bundles'][$product->id])) {
            return back()->withErrors(['item' => 'Balíček není v košíku.']);
        }
        $current = (int) $cart['bundles'][$product->id];
        $newAmount = (int) $data['amount'];
        $delta = $newAmount - $current; // positive to add more, negative to reduce

        if ($delta !== 0) {
            if ($delta > 0 && !empty($cart['start_date']) && !empty($cart['end_date'])) {
                // Validate additional components availability
                foreach ($product->bundleItems as $child) {
                    $available = $child->availableForRange($cart['start_date'], $cart['end_date']);
                    $existing = (int) Arr::get($cart['items'], $child->id, 0);
                    $required = (int) $child->pivot->amount * $delta;
                    if (($existing + $required) > $available) {
                        return back()->withErrors(['amount' => 'Požadované množství bundlu není pro zadaný termín dostupné.'])->withInput();
                    }
                }
            }
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
        return back()->with('success', 'Množství balíčku bylo aktualizováno.');
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
        return back()->with('success', 'Položka byla odebrána z košíku.');
    }

    public function removeBundle(Request $request, Product $product): RedirectResponse
    {
        // $product is the bundle product
        $cart = $this->getCart($request);
        if (!isset($cart['bundles'][$product->id])) {
            return back()->withErrors(['item' => 'Balíček není v košíku.']);
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
        return back()->with('success', 'Balíček byl odebrán z košíku.');
    }

    public function clear(Request $request): RedirectResponse
    {
        $request->session()->forget('cart');
        return back()->with('success', 'Košík byl vyprázdněn.');
    }

    public function checkout(Request $request): RedirectResponse
    {
        $cart = $this->getCart($request);
        if (empty($cart['items'])) {
            return back()->withErrors(['cart' => 'Košík je prázdný.']);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        if (empty($cart['start_date']) || empty($cart['end_date'])) {
            return back()->withErrors(['dates' => 'Nejprve nastavte termín rezervace.']);
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
                    return back()->withErrors(['cart' => 'Některé položky nejsou dostupné v požadovaném množství.'])->withInput();
                }
            }

            // Calculate total price for the reservation
            try {
                $start = \Carbon\Carbon::parse($cart['start_date']);
                $end = \Carbon\Carbon::parse($cart['end_date']);
                $minutes = $start->diffInMinutes($end);
                $days = max(1, (int) ceil($minutes / (60 * 24)));
            } catch (\Throwable $e) {
                DB::rollBack();
                return back()->withErrors(['dates' => 'Neplatný termín rezervace.'])->withInput();
            }

            $totalPrice = 0.0;
            foreach ($cart['items'] as $productId => $amount) {
                $product = Product::findOrFail($productId);
                $totalPrice += ((float) ($product->price_per_day ?? 0)) * (int) $amount * $days;
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
            return back()->withErrors(['server' => 'Došlo k chybě při vytváření rezervace.'])->withInput();
        }

        // Clear cart
        $request->session()->forget('cart');

        return redirect()->route('products.index')->with('success', 'Rezervace byla vytvořena. Děkujeme!');
    }
}
