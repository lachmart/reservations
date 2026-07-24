<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Product;
use App\Models\Tag;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Server\Tools\Annotations\Title;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[Title('Set product tags')]
class SyncProductTags extends BaseTool
{
    public function description(): string
    {
        return 'Set the tags attached to a product. Provide a comma-separated list of tag ids; '
            .'this replaces the product\'s current tags. Pass an empty string to remove all tags.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->integer('product_id')->description('The product to update.')->required()
            ->string('tag_ids')->description('Comma-separated tag ids, e.g. "1,4,7". Empty to clear.')->required();
    }

    protected function run(array $arguments): ToolResult
    {
        $validator = Validator::make($arguments, [
            'product_id' => ['required', 'integer'],
            'tag_ids' => ['present', 'string'],
        ]);

        if ($validator->fails()) {
            return ToolResult::error($validator->errors()->first());
        }

        $product = Product::find($arguments['product_id']);

        if (! $product) {
            return ToolResult::error("No product found with id {$arguments['product_id']}.");
        }

        $ids = collect(explode(',', (string) $arguments['tag_ids']))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->values();

        $existing = Tag::whereIn('id', $ids)->pluck('id');
        $missing = $ids->diff($existing);

        if ($missing->isNotEmpty()) {
            return ToolResult::error('Unknown tag id(s): '.$missing->implode(', ').'.');
        }

        $product->tags()->sync($existing->all());

        return ToolResult::json([
            'updated' => true,
            'product_id' => $product->id,
            'tag_ids' => $existing->all(),
        ]);
    }
}
