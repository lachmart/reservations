<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Category;
use App\Models\Product;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Attributes\Title;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;

#[Title('List products')]
#[IsReadOnly]
class ListProducts extends BaseTool
{
    public function description(): string
    {
        return 'List rentable products with their price per day and total stock. Use '
            .'check-product-availability to see how much is free for a specific date range.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()
                ->description('Filter products whose name contains this text.'),
            'category_id' => $schema->integer()
                ->description('Only products in this category (a section includes its subcategories).'),
            'available_only' => $schema->boolean()
                ->description('Only products with positive price and stock.'),
            'limit' => $schema->integer()
                ->description('Maximum number of products to return (default 50, max 200).'),
        ];
    }

    protected function run(array $arguments): Response
    {
        $query = Product::query()->with(['category.parent', 'bundleItems']);

        if (! empty($arguments['search'])) {
            $query->where('name', 'like', '%'.$arguments['search'].'%');
        }

        if (! empty($arguments['category_id'])) {
            // A section includes its subcategories.
            $category = Category::find((int) $arguments['category_id']);
            $category
                ? $query->inCategory($category)
                : $query->whereRaw('1 = 0');
        }

        if (! empty($arguments['available_only'])) {
            $query->available();
        }

        $limit = (int) ($arguments['limit'] ?? 50);
        $limit = max(1, min($limit, 200));

        $products = $query->orderBy('name')->limit($limit)->get();

        return Response::json([
            'count' => $products->count(),
            'products' => $products->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category?->full_name,
                'price_per_day' => $p->price_per_day,
                'stock' => $p->stock,
                'is_bundle' => $p->bundleItems->isNotEmpty(),
                'url' => $p->slug ? route('products.show', $p) : null,
                'components' => $p->bundleItems->isNotEmpty()
                    ? $p->bundleItems->map(fn (Product $c) => [
                        'product_id' => $c->id,
                        'name' => $c->name,
                        'quantity' => (int) $c->pivot->amount,
                    ])->values()->all()
                    : null,
            ])->all(),
        ]);
    }
}
