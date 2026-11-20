<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Attributes\Title;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;

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

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('The product id to delete.')
                ->required(),
            'force' => $schema->boolean()
                ->description('Delete even if used by reservations (also removes those line items). Default false.'),
        ];
    }

    protected function run(array $arguments): Response
    {
        $validator = Validator::make($arguments, [
            'id' => ['required', 'integer'],
            'force' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->first());
        }

        $product = Product::withCount('reservationItems')->find($arguments['id']);

        if (! $product) {
            return Response::error("No product found with id {$arguments['id']}.");
        }

        $force = $arguments['force'] ?? false;

        if ($product->reservationItems_count > 0 && ! $force) {
            return Response::error(
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

        return Response::json(['deleted' => true, 'id' => $arguments['id'], 'name' => $product->name]);
    }
}
