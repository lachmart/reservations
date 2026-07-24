<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Tag;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\Annotations\Title;
use Laravel\Mcp\Server\Tools\ToolResult;

#[Title('List tags')]
#[IsReadOnly]
class ListTags extends BaseTool
{
    public function description(): string
    {
        return 'List all tags with their product counts.';
    }

    protected function run(array $arguments): ToolResult
    {
        $tags = Tag::withCount('products')->orderBy('name')->get();

        return ToolResult::json([
            'count' => $tags->count(),
            'tags' => $tags->map(fn (Tag $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'products_count' => $t->products_count,
            ])->all(),
        ]);
    }
}
