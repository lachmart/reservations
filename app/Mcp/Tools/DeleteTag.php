<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Tag;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Attributes\Title;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;

#[Title('Delete tag')]
#[IsDestructive]
class DeleteTag extends BaseTool
{
    public function description(): string
    {
        return 'Delete a tag. It is detached from any products first (products are not deleted).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('The tag id to delete.')
                ->required(),
        ];
    }

    protected function run(array $arguments): Response
    {
        $tag = Tag::find($arguments['id'] ?? null);

        if (! $tag) {
            return Response::error('No tag found with id '.($arguments['id'] ?? 'null').'.');
        }

        $tag->products()->detach();
        $tag->delete();

        return Response::json(['deleted' => true, 'id' => $arguments['id'], 'name' => $tag->name]);
    }
}
