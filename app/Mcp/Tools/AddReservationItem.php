<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Product;
use App\Models\Reservation;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Server\Tools\Annotations\Title;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[Title('Add reservation item')]
class AddReservationItem extends BaseTool
{
    public function description(): string
    {
        return 'Add a product line item to a reservation. Validates that enough stock is '
            .'available for the reservation date range before adding.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->integer('reservation_id')->description('The reservation to add the item to.')->required()
            ->integer('product_id')->description('The product being reserved.')->required()
            ->integer('amount')->description('How many units to reserve (default 1).');
    }

    protected function run(array $arguments): ToolResult
    {
        $validator = Validator::make($arguments, [
            'reservation_id' => ['required', 'integer'],
            'product_id' => ['required', 'integer'],
            'amount' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return ToolResult::error($validator->errors()->first());
        }

        $reservation = Reservation::find($arguments['reservation_id']);

        if (! $reservation) {
            return ToolResult::error("No reservation found with id {$arguments['reservation_id']}.");
        }

        $product = Product::find($arguments['product_id']);

        if (! $product) {
            return ToolResult::error("No product found with id {$arguments['product_id']}.");
        }

        $amount = (int) ($arguments['amount'] ?? 1);

        $available = $product->availableForRange(
            (string) $reservation->start_date,
            (string) $reservation->end_date,
        );

        if ($amount > $available) {
            return ToolResult::error(
                "Only {$available} unit(s) of \"{$product->name}\" are available for this "
                ."reservation's date range; requested {$amount}."
            );
        }

        $item = $reservation->reservationItems()->create([
            'product_id' => $product->id,
            'amount' => $amount,
        ]);

        return ToolResult::json([
            'added' => true,
            'item_id' => $item->id,
            'reservation_id' => $reservation->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'amount' => $item->amount,
            'remaining_available' => $available - $amount,
        ]);
    }
}
