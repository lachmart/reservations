<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Server\Tools\Annotations\Title;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[Title('Create product')]
class CreateProduct extends BaseTool
{
    public function description(): string
    {
        return 'Create a rentable product with a name, price per day, stock and a category. '
            .'Link tags and ports afterwards with the dedicated tools.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->string('name')->description('Product name.')->required()
            ->integer('category_id')->description('Category id this product belongs to (required).')->required()
            ->string('description')->description('Product description.')
            ->string('status')->description('Product status label.')
            ->number('price_per_day')->description('Rental price per day. Defaults to 0.')
            ->integer('stock')->description('Total units in stock. Defaults to 0.');
    }

    protected function run(array $arguments): ToolResult
    {
        $validator = Validator::make($arguments, [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:255'],
            'price_per_day' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return ToolResult::error($validator->errors()->first());
        }

        $data = $validator->validated();

        if (! Category::whereKey($data['category_id'])->exists()) {
            return ToolResult::error("No category found with id {$data['category_id']}.");
        }

        $product = Product::create([
            'name' => $data['name'],
            // The live products table has NOT NULL description/status columns.
            'description' => $data['description'] ?? '',
            'status' => $data['status'] ?? '',
            'category_id' => $data['category_id'],
            'price_per_day' => $data['price_per_day'] ?? 0,
            'stock' => $data['stock'] ?? 0,
        ]);

        return ToolResult::json([
            'created' => true,
            'id' => $product->id,
            'name' => $product->name,
            'category_id' => $product->category_id,
            'price_per_day' => $product->price_per_day,
            'stock' => $product->stock,
        ]);
    }
}
