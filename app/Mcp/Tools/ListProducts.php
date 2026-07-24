<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Product;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\Annotations\Title;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[Title('List products')]
#[IsReadOnly]
class ListProducts extends BaseTool
{
    public function description(): string
    {
        return 'List rentable products with their price per day and total stock. Use '
            .'check-product-availability to see how much is free for a specific date range.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->string('search')->description('Filter products whose name contains this text.')
            ->integer('category_id')->description('Only products in this category.')
            ->boolean('available_only')->description('Only products with positive price and stock.')
            ->integer('limit')->description('Maximum number of products to return (default 50, max 200).');
    }

    protected function run(array $arguments): ToolResult
    {
        $query = Product::query()->with('category');

        if (! empty($arguments['search'])) {
            $query->where('name', 'like', '%'.$arguments['search'].'%');
        }

        if (! empty($arguments['category_id'])) {
            $query->where('category_id', (int) $arguments['category_id']);
        }

        if (! empty($arguments['available_only'])) {
            $query->available();
        }

        $limit = (int) ($arguments['limit'] ?? 50);
        $limit = max(1, min($limit, 200));

        $products = $query->orderBy('name')->limit($limit)->get();

        return ToolResult::json([
            'count' => $products->count(),
            'products' => $products->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'category' => optional($p->category)->name,
                'price_per_day' => $p->price_per_day,
                'stock' => $p->stock,
                'is_bundle' => $p->isBundle(),
            ])->all(),
        ]);
    }
}
