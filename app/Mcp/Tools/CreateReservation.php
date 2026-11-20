<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Exceptions\ItemsUnavailable;
use App\Models\Reservation;
use App\Services\ReservationBooking;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Laravel\Mcp\Server\Attributes\Title;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;

/**
 * Public booking: creates a reservation with its items in one go, priced and
 * checked by the server exactly like a reservation sent from the web cart.
 */
#[Title('Create reservation')]
class CreateReservation extends BaseTool
{
    /** Fields only the admin server may set; the public server rejects them. */
    protected const ADMIN_FIELDS = ['price', 'is_active', 'is_paid'];

    /** Reservations one client (IP) may create per window. */
    protected const RATE_LIMIT = 5;

    protected const RATE_WINDOW_SECONDS = 600;

    public function description(): string
    {
        return 'Create a reservation with its items in one call. Pass every product and '
            .'quantity in `items`; bundles (sets) are expanded automatically. The server '
            .'checks availability for the whole term and computes the price from the '
            .'products and number of days (every started 24 hours is one day). If any item '
            .'is short, nothing is saved and the error lists what is missing. Give a phone '
            .'or an e-mail (at least one). The reservation is created unconfirmed and '
            .'unpaid; the rental company confirms it and the final price including delivery.';
    }

    public function schema(JsonSchema $schema): array
    {
        $zones = ReservationBooking::deliveryZones();

        $properties = [
            'name' => $schema->string()->description('Customer name.')->required(),
            'phone' => $schema->string()->description('Customer phone. Phone or email is required.'),
            'email' => $schema->string()->description('Customer email. Phone or email is required.'),
            'start_date' => $schema->string()->description('Rental start (ISO-8601), e.g. "2027-07-06 10:00".')->required(),
            'end_date' => $schema->string()->description('Rental end (ISO-8601), must be after start_date.')->required(),
            'items' => $schema->array()
                ->description('Products to rent. Use product ids from list-products.')
                ->items($schema->object([
                    'product_id' => $schema->integer()->description('Product (or bundle) id.')->required(),
                    'quantity' => $schema->integer()->min(1)->description('Number of units.')->required(),
                ]))
                ->required(! $this->isAdmin()),
            'event_place' => $schema->string()->description('Where the event takes place (town, venue). Needed to price delivery.'),
            'event_type' => $schema->string()->description('Kind of event: '.implode(', ', ReservationBooking::EVENT_TYPES).'.'),
            'delivery_zone' => $schema->string()
                ->enum(array_keys($zones))
                ->description('Transport zone (delivery and pick-up); its price is added to the total. '
                    .collect($zones)
                        ->map(fn ($z, $k) => "{$k}: {$z['label']} (".(is_null($z['price']) ? 'quoted later' : "{$z['price']} CZK").')')
                        ->implode('; ').'.'),
            'service' => $schema->boolean()->description('Customer wants installation, wiring or an operator on site. Extra cost, quoted later.'),
            'delivery' => $schema->boolean()->description('Use delivery_zone instead. true without a zone = delivery wanted, priced later.'),
            'note' => $schema->string()->description('Free-form note for the rental company.'),
        ];

        if ($this->isAdmin()) {
            $properties += [
                'price' => $schema->number()->description('Override the computed total price.'),
                'is_active' => $schema->boolean()->description('Whether the reservation is active. Defaults to true.'),
                'is_paid' => $schema->boolean()->description('Whether the reservation is paid. Defaults to false.'),
            ];
        }

        return $properties;
    }

    protected function run(array $arguments): Response
    {
        $forbidden = array_values(array_intersect(self::ADMIN_FIELDS, array_keys($arguments)));
        if (! $this->isAdmin() && $forbidden) {
            return Response::error(
                'These fields cannot be set on a public reservation: '.implode(', ', $forbidden)
                .'. The price is calculated by the server from the items and the term.'
            );
        }

        $validator = Validator::make($arguments, $this->rules(), ReservationBooking::messages());

        if ($validator->fails()) {
            return Response::error($validator->errors()->first());
        }

        $data = $validator->validated();

        if (! $this->isAdmin()) {
            $key = 'mcp-create-reservation:'.request()->ip();
            if (RateLimiter::tooManyAttempts($key, self::RATE_LIMIT)) {
                return Response::error('Too many reservations from this client. Try again in '
                    .RateLimiter::availableIn($key).' seconds, or call the rental company.');
            }
            RateLimiter::hit($key, self::RATE_WINDOW_SECONDS);
        }

        $booking = app(ReservationBooking::class);

        try {
            $selection = ReservationBooking::selectionFromLines($data['items'] ?? []);
            $reservation = $booking->book(
                $data,
                $data['start_date'],
                $data['end_date'],
                $selection['items'],
                $selection['bundles'],
                $this->isAdmin() ? 'admin' : 'mcp',
            );
        } catch (ItemsUnavailable $e) {
            return Response::error($e->getMessage().' Nothing was saved. Check availability with check-product-availability and adjust the items or the term.');
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage().' Nothing was saved.');
        }

        $this->afterCreate($reservation, $data);

        return Response::json($this->present($reservation->fresh(['reservationItems.product'])));
    }

    protected function isAdmin(): bool
    {
        return false;
    }

    protected function rules(): array
    {
        return ReservationBooking::contactRules() + ReservationBooking::eventRules() + [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    /**
     * Public reservations notify the customer and the owner, like the web cart.
     */
    protected function afterCreate(Reservation $reservation, array $data): void
    {
        app(ReservationBooking::class)->notify($reservation);
    }

    protected function present(Reservation $reservation): array
    {
        return [
            'created' => true,
            'id' => $reservation->id,
            'status' => $reservation->is_paid ? 'paid' : 'awaiting confirmation, unpaid',
            'name' => $reservation->name,
            'phone' => $reservation->phone ?: null,
            'email' => $reservation->email ?: null,
            'start_date' => \App\Support\LocalTime::iso($reservation->start_date),
            'end_date' => \App\Support\LocalTime::iso($reservation->end_date),
            'days' => ReservationBooking::days((string) $reservation->start_date, (string) $reservation->end_date),
            'price' => (float) $reservation->price,
            'rental_price' => (float) $reservation->price - (float) ($reservation->delivery_price ?? 0),
            'delivery_price' => is_null($reservation->delivery_price) ? null : (float) $reservation->delivery_price,
            'currency' => 'CZK',
            'price_note' => match (true) {
                ! $reservation->delivery => 'Rental price; the customer picks up the gear in Olomouc.',
                is_null($reservation->delivery_price) => 'Rental price only. Transport is quoted by place and confirmed by the rental company.',
                default => 'Rental plus estimated transport (delivery and pick-up). The rental company confirms the final price.',
            },
            'delivery_zone' => $reservation->delivery_zone,
            'service' => (bool) $reservation->service,
            'service_note' => $reservation->service ? 'Installation / operator requested; quoted separately, not included in price.' : null,
            'event_place' => $reservation->event_place,
            'event_type' => $reservation->event_type,
            'delivery' => (bool) $reservation->delivery,
            'items' => $reservation->reservationItems->map(fn ($item) => [
                'product_id' => $item->product_id,
                'product_name' => optional($item->product)->name,
                'quantity' => (int) $item->amount,
            ])->values()->all(),
        ];
    }
}
