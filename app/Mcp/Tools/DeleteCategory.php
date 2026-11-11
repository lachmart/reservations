<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Category;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\Title;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[Title('Delete category')]
#[IsDestructive]
class DeleteCategory extends BaseTool
{
    public function description(): string
    {
        return 'Delete a category. Refuses if it still has products (reassign them to another '
            .'category first) — products require a category, so an in-use category cannot be removed.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->integer('id')->description('The category id to delete.')->required();
    }

    protected function run(array $arguments): ToolResult
    {
        $category = Category::withCount('products')->find($arguments['id'] ?? null);

        if (! $category) {
            return ToolResult::error('No category found with id '.($arguments['id'] ?? 'null').'.');
        }

        if ($category->products_count > 0) {
            return ToolResult::error(
                "Category \"{$category->name}\" still has {$category->products_count} product(s). "
                .'Reassign them to another category before deleting.'
            );
        }

        $category->delete();

        return ToolResult::json(['deleted' => true, 'id' => $arguments['id'], 'name' => $category->name]);
    }
}
