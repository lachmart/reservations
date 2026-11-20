<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Reservation;
use App\Models\ReservationItem;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Attributes\Title;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;

#[Title('Get reservation')]
#[IsReadOnly]
class GetReservation extends BaseTool
{
    public function description(): string
    {
        return 'Fetch a single reservation by id, including its line items with product names.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('The reservation id.')
                ->required(),
        ];
    }

    protected function run(array $arguments): Response
    {
        $reservation = Reservation::with('reservationItems.product')->find($arguments['id']);

        if (! $reservation) {
            return Response::error("No reservation found with id {$arguments['id']}.");
        }

        return Response::json([
            'id' => $reservation->id,
            'name' => $reservation->name,
            'email' => $reservation->email,
            'phone' => $reservation->phone,
            'start_date' => \App\Support\LocalTime::iso($reservation->start_date),
            'end_date' => \App\Support\LocalTime::iso($reservation->end_date),
            'price' => $reservation->price,
            'note' => $reservation->note,
            'event_place' => $reservation->event_place,
            'event_type' => $reservation->event_type,
            'delivery' => (bool) $reservation->delivery,
            'delivery_zone' => $reservation->delivery_zone,
            'delivery_price' => $reservation->delivery_price,
            'service' => (bool) $reservation->service,
            'source' => $reservation->source,
            'is_active' => $reservation->is_active,
            'is_paid' => $reservation->is_paid,
            'items' => $reservation->reservationItems->map(fn (ReservationItem $item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => optional($item->product)->name,
                'amount' => $item->amount,
            ])->all(),
        ]);
    }
}
