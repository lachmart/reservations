<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Category;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Attributes\Title;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;

#[Title('Delete category')]
#[IsDestructive]
class DeleteCategory extends BaseTool
{
    public function description(): string
    {
        return 'Delete a category. Refuses if it still has products (reassign them to another '
            .'category first) — products require a category, so an in-use category cannot be removed.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('The category id to delete.')
                ->required(),
        ];
    }

    protected function run(array $arguments): Response
    {
        $category = Category::withCount('products')->find($arguments['id'] ?? null);

        if (! $category) {
            return Response::error('No category found with id '.($arguments['id'] ?? 'null').'.');
        }

        if ($category->children()->exists()) {
            return Response::error(
                "Category \"{$category->name}\" has subcategories. Move or delete them first."
            );
        }

        if ($category->products_count > 0) {
            return Response::error(
                "Category \"{$category->name}\" still has {$category->products_count} product(s). "
                .'Reassign them to another category before deleting.'
            );
        }

        $category->delete();

        return Response::json(['deleted' => true, 'id' => $arguments['id'], 'name' => $category->name]);
    }
}
