<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\Title;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[Title('Delete product')]
#[IsDestructive]
class DeleteProduct extends BaseTool
{
    public function description(): string
    {
        return 'Delete a product. Refuses if it is used by any reservation (which would corrupt '
            .'those bookings) unless force=true, in which case the referencing reservation line '
            .'items are removed too. Media, tag and port links are detached.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->integer('id')->description('The product id to delete.')->required()
            ->boolean('force')->description('Delete even if used by reservations (also removes those line items). Default false.');
    }

    protected function run(array $arguments): ToolResult
    {
        $validator = Validator::make($arguments, [
            'id' => ['required', 'integer'],
            'force' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return ToolResult::error($validator->errors()->first());
        }

        $product = Product::withCount('reservationItems')->find($arguments['id']);

        if (! $product) {
            return ToolResult::error("No product found with id {$arguments['id']}.");
        }

        $force = $arguments['force'] ?? false;

        if ($product->reservationItems_count > 0 && ! $force) {
            return ToolResult::error(
                "Product \"{$product->name}\" is used by {$product->reservationItems_count} reservation "
                .'line item(s). Pass force=true to delete it and those line items anyway.'
            );
        }

        if ($force) {
            $product->reservationItems()->delete();
        }

        $product->tags()->detach();
        $product->ports()->detach();
        $product->clearMediaCollection('avatar');
        $product->clearMediaCollection('product-images');
        $product->delete();

        return ToolResult::json(['deleted' => true, 'id' => $arguments['id'], 'name' => $product->name]);
    }
}
