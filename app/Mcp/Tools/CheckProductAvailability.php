<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\Annotations\Title;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[Title('Check product availability')]
#[IsReadOnly]
class CheckProductAvailability extends BaseTool
{
    public function description(): string
    {
        return 'Report how many units of a product are still available for a given date '
            .'range, accounting for overlapping reservations.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->integer('product_id')->description('The product id to check.')->required()
            ->string('start_date')->description('Range start (ISO-8601).')->required()
            ->string('end_date')->description('Range end (ISO-8601), must be after start_date.')->required();
    }

    protected function run(array $arguments): ToolResult
    {
        $validator = Validator::make($arguments, [
            'product_id' => ['required', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);

        if ($validator->fails()) {
            return ToolResult::error($validator->errors()->first());
        }

        $product = Product::find($arguments['product_id']);

        if (! $product) {
            return ToolResult::error("No product found with id {$arguments['product_id']}.");
        }

        $available = $product->availableForRange($arguments['start_date'], $arguments['end_date']);
        $reserved = $product->reservedAmountBetween($arguments['start_date'], $arguments['end_date']);

        return ToolResult::json([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'start_date' => $arguments['start_date'],
            'end_date' => $arguments['end_date'],
            'total_stock' => (int) $product->stock,
            'reserved' => $reserved,
            'available' => $available,
        ]);
    }
}
