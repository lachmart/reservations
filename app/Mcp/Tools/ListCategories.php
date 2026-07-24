<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Category;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\Annotations\Title;
use Laravel\Mcp\Server\Tools\ToolResult;

#[Title('List categories')]
#[IsReadOnly]
class ListCategories extends BaseTool
{
    public function description(): string
    {
        return 'List all product categories with their product counts.';
    }

    protected function run(array $arguments): ToolResult
    {
        $categories = Category::withCount('products')->orderBy('name')->get();

        return ToolResult::json([
            'count' => $categories->count(),
            'categories' => $categories->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'products_count' => $c->products_count,
            ])->all(),
        ]);
    }
}
