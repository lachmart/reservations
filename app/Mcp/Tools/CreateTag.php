<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Tag;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Server\Attributes\Title;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;

#[Title('Create tag')]
class CreateTag extends BaseTool
{
    public function description(): string
    {
        return 'Create a new tag that can be attached to products.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('Tag name.')
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

        $tag = Tag::create(['name' => $arguments['name']]);

        return Response::json([
            'created' => true,
            'id' => $tag->id,
            'name' => $tag->name,
        ]);
    }
}
