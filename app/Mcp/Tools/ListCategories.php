<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Category;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Response;

#[Title('List categories')]
#[IsReadOnly]
class ListCategories extends BaseTool
{
    public function description(): string
    {
        return 'List all product categories in menu order: sections (parent_id null) followed by '
            .'their subcategories. products_count counts products directly in the category; a '
            .'section\'s listing on the website also shows its subcategories\' products.';
    }

    protected function run(array $arguments): Response
    {
        $all = Category::withCount('products')->ordered()->get();
        $children = $all->whereNotNull('parent_id')->groupBy('parent_id');

        // Sections first, each followed by its subcategories; orphans (a parent
        // that no longer exists) are listed at the end.
        $ordered = $all->whereNull('parent_id')
            ->flatMap(fn (Category $s) => collect([$s])->concat($children[$s->id] ?? []));
        $ordered = $ordered->concat($all->diff($ordered));

        return Response::json([
            'count' => $all->count(),
            'categories' => $ordered->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'parent_id' => $c->parent_id,
                'position' => $c->position,
                'products_count' => $c->products_count,
                'url' => route('products.index', ['category' => $c->slug]),
            ])->values()->all(),
        ]);
    }
}
