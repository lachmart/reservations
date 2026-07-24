<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Server\Tools\Annotations\Title;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[Title('Create category')]
class CreateCategory extends BaseTool
{
    public function description(): string
    {
        return 'Create a new product category.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->string('name')->description('Category name.')->required();
    }

    protected function run(array $arguments): ToolResult
    {
        $validator = Validator::make($arguments, [
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return ToolResult::error($validator->errors()->first());
        }

        $category = Category::create(['name' => $arguments['name']]);

        return ToolResult::json([
            'created' => true,
            'id' => $category->id,
            'name' => $category->name,
        ]);
    }
}
