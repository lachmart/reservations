<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\Title;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[Title('Update product')]
#[IsIdempotent]
class UpdateProduct extends BaseTool
{
    public function description(): string
    {
        return 'Update fields on an existing product. Only the fields you provide are changed. '
            .'Useful for adjusting price, stock, description or category.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->integer('id')->description('The product id to update.')->required()
            ->string('name')->description('Product name.')
            ->string('description')->description('Product description.')
            ->string('status')->description('Product status label.')
            ->integer('category_id')->description('Category id this product belongs to.')
            ->number('price_per_day')->description('Rental price per day.')
            ->integer('stock')->description('Total units in stock.');
    }

    protected function run(array $arguments): ToolResult
    {
        $product = Product::find($arguments['id'] ?? null);

        if (! $product) {
            return ToolResult::error('No product found with id '.($arguments['id'] ?? 'null').'.');
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
            return ToolResult::error($validator->errors()->first());
        }

        $data = $validator->validated();

        if ($data === []) {
            return ToolResult::error('No fields provided to update.');
        }

        if (! empty($data['category_id']) && ! Category::whereKey($data['category_id'])->exists()) {
            return ToolResult::error("No category found with id {$data['category_id']}.");
        }

        $product->fill($data)->save();

        return ToolResult::json([
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
