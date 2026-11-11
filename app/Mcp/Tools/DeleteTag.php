<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Tag;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\Title;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[Title('Delete tag')]
#[IsDestructive]
class DeleteTag extends BaseTool
{
    public function description(): string
    {
        return 'Delete a tag. It is detached from any products first (products are not deleted).';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->integer('id')->description('The tag id to delete.')->required();
    }

    protected function run(array $arguments): ToolResult
    {
        $tag = Tag::find($arguments['id'] ?? null);

        if (! $tag) {
            return ToolResult::error('No tag found with id '.($arguments['id'] ?? 'null').'.');
        }

        $tag->products()->detach();
        $tag->delete();

        return ToolResult::json(['deleted' => true, 'id' => $arguments['id'], 'name' => $tag->name]);
    }
}
