<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Server\Attributes\Title;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;

#[Title('Create category')]
class CreateCategory extends BaseTool
{
    public function description(): string
    {
        return 'Create a product category: a section (no parent_id) or a subcategory of a section '
            .'(parent_id = the section\'s id; only two levels).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('Category name.')
                ->required(),
            'parent_id' => $schema->integer()
                ->description('Section this subcategory belongs to. Omit for a new section.'),
            'position' => $schema->integer()
                ->description('Order in the menu among its siblings (lower first). Defaults to last.'),
        ];
    }

    protected function run(array $arguments): Response
    {
        $validator = Validator::make($arguments, [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer'],
            'position' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->first());
        }

        $parentId = $arguments['parent_id'] ?? null;
        if ($parentId !== null && $error = UpdateCategory::parentError((int) $parentId)) {
            return Response::error($error);
        }

        $position = $arguments['position']
            ?? (int) Category::where('parent_id', $parentId)->max('position') + 1;

        $category = Category::create([
            'name' => $arguments['name'],
            'parent_id' => $parentId,
            'position' => $position,
        ]);

        return Response::json([
            'created' => true,
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'parent_id' => $category->parent_id,
            'position' => $category->position,
        ]);
    }
}
