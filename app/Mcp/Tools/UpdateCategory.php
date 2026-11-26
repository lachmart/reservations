<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Server\Attributes\Title;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;

#[Title('Update category')]
class UpdateCategory extends BaseTool
{
    public function description(): string
    {
        return 'Rename a category, move it under a section (parent_id) or make it a section '
            .'(parent_id 0), or change its menu position. Only the fields you provide change. '
            .'The slug (URL) stays the same.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('The category id to update.')
                ->required(),
            'name' => $schema->string()
                ->description('New name.'),
            'parent_id' => $schema->integer()
                ->description('Section to move it under; 0 makes it a section itself.'),
            'position' => $schema->integer()
                ->description('Order in the menu among its siblings (lower first).'),
        ];
    }

    /** Why a category can't be the parent of a subcategory, or null if it can. */
    public static function parentError(int $parentId, ?Category $child = null): ?string
    {
        $parent = Category::find($parentId);

        return match (true) {
            ! $parent => "No category found with id {$parentId}.",
            $child && $parent->is($child) => 'A category cannot be its own parent.',
            $parent->parent_id !== null => "\"{$parent->name}\" is itself a subcategory; only two levels are supported.",
            default => null,
        };
    }

    protected function run(array $arguments): Response
    {
        $validator = Validator::make($arguments, [
            'id' => ['required', 'integer'],
            'name' => ['sometimes', 'string', 'max:255'],
            'parent_id' => ['sometimes', 'integer', 'min:0'],
            'position' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->first());
        }

        $category = Category::find($arguments['id']);
        if (! $category) {
            return Response::error("No category found with id {$arguments['id']}.");
        }

        if (array_key_exists('parent_id', $arguments)) {
            $parentId = (int) $arguments['parent_id'] ?: null;
            if ($parentId !== null) {
                if ($error = self::parentError($parentId, $category)) {
                    return Response::error($error);
                }
                if ($category->children()->exists()) {
                    return Response::error("\"{$category->name}\" has subcategories, so it must stay a section.");
                }
            }
            $category->parent_id = $parentId;
        }

        foreach (['name', 'position'] as $field) {
            if (array_key_exists($field, $arguments)) {
                $category->{$field} = $arguments[$field];
            }
        }

        $changed = array_keys($category->getDirty());
        $category->save();

        return Response::json([
            'updated' => true,
            'changed_fields' => $changed,
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'parent_id' => $category->parent_id,
            'position' => $category->position,
        ]);
    }
}
