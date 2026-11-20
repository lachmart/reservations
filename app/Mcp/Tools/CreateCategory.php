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
        return 'Create a new product category.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('Category name.')
                ->required(),
        ];
    }

    protected function run(array $arguments): Response
    {
        $validator = Validator::make($arguments, [
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->first());
        }

        $category = Category::create(['name' => $arguments['name']]);

        return Response::json([
            'created' => true,
            'id' => $category->id,
            'name' => $category->name,
        ]);
    }
}
