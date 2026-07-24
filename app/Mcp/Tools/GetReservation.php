<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Reservation;
use App\Models\ReservationItem;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\Annotations\Title;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[Title('Get reservation')]
#[IsReadOnly]
class GetReservation extends BaseTool
{
    public function description(): string
    {
        return 'Fetch a single reservation by id, including its line items with product names.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->integer('id')->description('The reservation id.')->required();
    }

    protected function run(array $arguments): ToolResult
    {
        $reservation = Reservation::with('reservationItems.product')->find($arguments['id']);

        if (! $reservation) {
            return ToolResult::error("No reservation found with id {$arguments['id']}.");
        }

        return ToolResult::json([
            'id' => $reservation->id,
            'name' => $reservation->name,
            'email' => $reservation->email,
            'phone' => $reservation->phone,
            'start_date' => optional($reservation->start_date)->toIso8601String(),
            'end_date' => optional($reservation->end_date)->toIso8601String(),
            'price' => $reservation->price,
            'note' => $reservation->note,
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
