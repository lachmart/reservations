<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Server\Attributes\Title;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;

#[Title('Create product')]
class CreateProduct extends BaseTool
{
    public function description(): string
    {
        return 'Create a rentable product with a name, price per day, stock and a category. '
            .'Link tags and ports afterwards with the dedicated tools.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('Product name.')
                ->required(),
            'category_id' => $schema->integer()
                ->description('Category id this product belongs to (required).')
                ->required(),
            'description' => $schema->string()
                ->description('Product description.'),
            'status' => $schema->string()
                ->description('Product status label.'),
            'price_per_day' => $schema->number()
                ->description('Rental price per day. Defaults to 0.'),
            'stock' => $schema->integer()
                ->description('Total units in stock. Defaults to 0.'),
        ];
    }

    protected function run(array $arguments): Response
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
            return Response::error($validator->errors()->first());
        }

        $data = $validator->validated();

        if (! Category::whereKey($data['category_id'])->exists()) {
            return Response::error("No category found with id {$data['category_id']}.");
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

        return Response::json([
            'created' => true,
            'id' => $product->id,
            'name' => $product->name,
            'category_id' => $product->category_id,
            'price_per_day' => $product->price_per_day,
            'stock' => $product->stock,
        ]);
    }
}
