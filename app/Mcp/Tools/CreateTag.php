<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Tag;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Server\Tools\Annotations\Title;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[Title('Create tag')]
class CreateTag extends BaseTool
{
    public function description(): string
    {
        return 'Create a new tag that can be attached to products.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->string('name')->description('Tag name.')->required();
    }

    protected function run(array $arguments): ToolResult
    {
        $validator = Validator::make($arguments, [
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return ToolResult::error($validator->errors()->first());
        }

        $tag = Tag::create(['name' => $arguments['name']]);

        return ToolResult::json([
            'created' => true,
            'id' => $tag->id,
            'name' => $tag->name,
        ]);
    }
}
