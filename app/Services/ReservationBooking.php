<?php

namespace App\Services;

use App\Exceptions\ItemsUnavailable;
use App\Mail\ReservationCreated;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\ReservationItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * The one place that turns a selection of products into a reservation.
 *
 * The web cart and the public MCP server both go through here, so they share the
 * same contact rules, price calculation, availability check and initial state.
 *
 * A selection is two maps, the same shape the cart keeps in the session:
 *   items   [product_id => units]  every physical unit, bundle contents expanded
 *   bundles [bundle_id  => count]  bundles chosen, priced by the bundle's own rate
 */
class ReservationBooking
{
    /** Event types offered in the cart and the enquiry form. */
    public const EVENT_TYPES = ['Svatba', 'Oslava / narozeniny', 'Ples', 'Firemní akce', 'Koncert', 'Festival', 'Divadlo', 'Konference', 'Jiné'];

    /**
     * Who is booking: a name plus at least a phone or an e-mail.
     * Shared by the cart, the enquiry form and the MCP server.
     */
    public static function contactRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:50'],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:255'],
        ];
    }

    /**
     * Event details used to price delivery and set-up.
     */
    public static function eventRules(): array
    {
        return [
            'event_place' => ['nullable', 'string', 'max:255'],
            'event_type' => ['nullable', 'string', 'max:100'],
            'delivery' => ['nullable', 'boolean'],
            'delivery_zone' => ['nullable', 'string', Rule::in(array_keys(self::deliveryZones()))],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, array{label:string, price:?int}>
     */
    public static function deliveryZones(): array
    {
        return config('contact.delivery_zones', []);
    }

    /**
     * Resolve the delivery fields from a zone (preferred) or the plain flag.
     *
     * @return array{delivery:bool, delivery_zone:?string, delivery_price:?float}
     */
    public static function delivery(array $details): array
    {
        $zone = $details['delivery_zone'] ?? null;
        $zones = self::deliveryZones();

        if ($zone && isset($zones[$zone])) {
            $price = $zones[$zone]['price'];

            return [
                'delivery' => $zone !== 'pickup',
                'delivery_zone' => $zone,
                'delivery_price' => is_null($price) ? null : (float) $price,
            ];
        }

        // No zone chosen: delivery wanted but not priced yet.
        return [
            'delivery' => (bool) ($details['delivery'] ?? false),
            'delivery_zone' => null,
            'delivery_price' => null,
        ];
    }

    public static function messages(): array
    {
        return [
            'name.required' => 'Vyplňte prosím jméno.',
            'phone.required_without' => 'Vyplňte telefon nebo e-mail, ať se vám můžeme ozvat.',
            'email.required_without' => 'Vyplňte telefon nebo e-mail, ať se vám můžeme ozvat.',
            'email.email' => 'E-mail nemá platný tvar.',
        ];
    }

    /**
     * Number of billed days: every started 24 hours counts, at least one.
     */
    public static function days(string $start, string $end): int
    {
        $minutes = Carbon::parse($start)->diffInMinutes(Carbon::parse($end));

        return max(1, (int) ceil($minutes / (60 * 24)));
    }

    /**
     * Build a selection from flat lines such as [{product_id, quantity}], expanding
     * bundles into their components the same way the cart does.
     *
     * @param  array<int, array{product_id:int, quantity:int}>  $lines
     * @return array{items: array<int,int>, bundles: array<int,int>}
     */
    public static function selectionFromLines(array $lines): array
    {
        $items = [];
        $bundles = [];

        $products = Product::with('bundleItems')
            ->whereIn('id', array_column($lines, 'product_id'))
            ->get()
            ->keyBy('id');

        foreach ($lines as $line) {
            $product = $products[$line['product_id']] ?? null;
            $qty = (int) $line['quantity'];

            if (! $product) {
                throw new InvalidArgumentException("No product found with id {$line['product_id']}.");
            }

            if ($product->bundleItems->isNotEmpty()) {
                $bundles[$product->id] = ($bundles[$product->id] ?? 0) + $qty;
                foreach ($product->bundleItems as $child) {
                    $items[$child->id] = ($items[$child->id] ?? 0) + (int) $child->pivot->amount * $qty;
                }
            } else {
                $items[$product->id] = ($items[$product->id] ?? 0) + $qty;
            }
        }

        return ['items' => $items, 'bundles' => $bundles];
    }

    /**
     * Total rental price: bundles at their own daily rate, other units at theirs,
     * times the number of days. Delivery is not included; it is priced per place.
     *
     * @param  array<int,int>  $items
     * @param  array<int,int>  $bundles
     */
    public static function price(array $items, array $bundles, int $days): float
    {
        $ids = array_unique(array_merge(array_keys($items), array_keys($bundles)));
        $products = Product::with('bundleItems')->whereIn('id', $ids)->get()->keyBy('id');

        $total = 0.0;
        $covered = [];

        foreach ($bundles as $bundleId => $count) {
            $bundle = $products[$bundleId] ?? null;
            if (! $bundle) {
                continue;
            }
            $total += (float) ($bundle->price_per_day ?? 0) * (int) $count * $days;
            foreach ($bundle->bundleItems as $child) {
                $covered[$child->id] = ($covered[$child->id] ?? 0) + (int) $child->pivot->amount * (int) $count;
            }
        }

        foreach ($items as $productId => $units) {
            $product = $products[$productId] ?? null;
            $standalone = (int) $units - (int) ($covered[$productId] ?? 0);
            if (! $product || $standalone <= 0) {
                continue;
            }
            $total += (float) ($product->price_per_day ?? 0) * $standalone * $days;
        }

        return $total;
    }

    /**
     * Check availability, price the selection and store the reservation with its
     * items in one transaction. Throws ItemsUnavailable (and writes nothing) when
     * any product is short in the term.
     *
     * The price and the paid/active flags are always set here, never by the caller.
     *
     * @param  array  $details  contact + event fields (see contactRules/eventRules)
     * @param  array<int,int>  $items
     * @param  array<int,int>  $bundles
     */
    public function book(array $details, string $start, string $end, array $items, array $bundles = [], string $source = 'web'): Reservation
    {
        if (Carbon::parse($end)->lessThanOrEqualTo(Carbon::parse($start))) {
            throw new InvalidArgumentException('End date must be after start date.');
        }

        return DB::transaction(function () use ($details, $start, $end, $items, $bundles, $source) {
            // Lock the product rows so two simultaneous bookings can't both take the last unit.
            $products = Product::whereIn('id', array_keys($items))->lockForUpdate()->get()->keyBy('id');

            $shortages = [];
            foreach ($items as $productId => $units) {
                $product = $products[$productId] ?? null;
                $available = $product ? $product->availableForRange($start, $end) : 0;
                if ((int) $units > $available) {
                    $shortages[] = [
                        'product_id' => (int) $productId,
                        'name' => $product->name ?? "#{$productId}",
                        'requested' => (int) $units,
                        'available' => $available,
                    ];
                }
            }

            if ($shortages) {
                throw new ItemsUnavailable($shortages);
            }

            $delivery = self::delivery($details);

            $reservation = Reservation::create([
                'name' => $details['name'],
                // The live columns are NOT NULL, so store empty strings, not null.
                'email' => $details['email'] ?? '',
                'phone' => $details['phone'] ?? '',
                'note' => $details['note'] ?? '',
                'event_place' => $details['event_place'] ?? null,
                'event_type' => $details['event_type'] ?? null,
                'delivery' => $delivery['delivery'],
                'delivery_zone' => $delivery['delivery_zone'],
                'delivery_price' => $delivery['delivery_price'],
                'source' => $source,
                'start_date' => $start,
                'end_date' => $end,
                // Rental plus the estimated delivery (when the zone has a price).
                'price' => self::price($items, $bundles, self::days($start, $end)) + ($delivery['delivery_price'] ?? 0),
                'is_active' => true,
                'is_paid' => false,
            ]);

            foreach ($items as $productId => $units) {
                ReservationItem::create([
                    'reservation_id' => $reservation->id,
                    'product_id' => (int) $productId,
                    'amount' => (int) $units,
                ]);
            }

            return $reservation;
        });
    }

    /**
     * Confirmation to the customer (when they left an e-mail) and a separate copy
     * to the owner inbox. Mail problems are logged, never thrown.
     */
    public function notify(Reservation $reservation): void
    {
        if (filled($reservation->email)) {
            try {
                Mail::to($reservation->email)->send(new ReservationCreated($reservation));
            } catch (\Throwable $e) {
                Log::warning('Reservation confirmation email failed: '.$e->getMessage(), [
                    'reservation_id' => $reservation->id,
                ]);
            }
        }

        // Owner copy as its own message (not BCC), so it lands in the inbox and a
        // problem with the customer's address can't stop it.
        try {
            if ($owners = config('contact.owner_notify')) {
                Mail::to($owners)->send(new ReservationCreated($reservation));
            }
        } catch (\Throwable $e) {
            Log::warning('Reservation owner notification failed: '.$e->getMessage(), [
                'reservation_id' => $reservation->id,
            ]);
        }
    }
}
