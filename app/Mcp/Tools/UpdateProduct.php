<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Attributes\Title;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;

#[Title('Update product')]
#[IsIdempotent]
class UpdateProduct extends BaseTool
{
    public function description(): string
    {
        return 'Update fields on an existing product. Only the fields you provide are changed. '
            .'Useful for adjusting price, stock, description or category.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('The product id to update.')
                ->required(),
            'name' => $schema->string()
                ->description('Product name.'),
            'description' => $schema->string()
                ->description('Product description.'),
            'status' => $schema->string()
                ->description('Product status label.'),
            'category_id' => $schema->integer()
                ->description('Category id this product belongs to.'),
            'price_per_day' => $schema->number()
                ->description('Rental price per day.'),
            'stock' => $schema->integer()
                ->description('Total units in stock.'),
        ];
    }

    protected function run(array $arguments): Response
    {
        $product = Product::find($arguments['id'] ?? null);

        if (! $product) {
            return Response::error('No product found with id '.($arguments['id'] ?? 'null').'.');
        }

        $validator = Validator::make($arguments, [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'nullable', 'string', 'max:255'],
            'category_id' => ['sometimes', 'nullable', 'integer'],
            'price_per_day' => ['sometimes', 'numeric', 'min:0'],
            'stock' => ['sometimes', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->first());
        }

        $data = $validator->validated();

        if ($data === []) {
            return Response::error('No fields provided to update.');
        }

        if (! empty($data['category_id']) && ! Category::whereKey($data['category_id'])->exists()) {
            return Response::error("No category found with id {$data['category_id']}.");
        }

        $product->fill($data)->save();

        return Response::json([
            'updated' => true,
            'id' => $product->id,
            'changed_fields' => array_keys($data),
            'name' => $product->name,
            'category_id' => $product->category_id,
            'price_per_day' => $product->price_per_day,
            'stock' => $product->stock,
        ]);
    }
}
