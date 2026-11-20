<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Product;
use App\Models\Tag;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Server\Attributes\Title;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;

#[Title('Set product tags')]
class SyncProductTags extends BaseTool
{
    public function description(): string
    {
        return 'Set the tags attached to a product. Provide a comma-separated list of tag ids; '
            .'this replaces the product\'s current tags. Pass an empty string to remove all tags.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'product_id' => $schema->integer()
                ->description('The product to update.')
                ->required(),
            'tag_ids' => $schema->string()
                ->description('Comma-separated tag ids, e.g. "1,4,7". Empty to clear.')
                ->required(),
        ];
    }

    protected function run(array $arguments): Response
    {
        $validator = Validator::make($arguments, [
            'product_id' => ['required', 'integer'],
            'tag_ids' => ['present', 'string'],
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->first());
        }

        $product = Product::find($arguments['product_id']);

        if (! $product) {
            return Response::error("No product found with id {$arguments['product_id']}.");
        }

        $ids = collect(explode(',', (string) $arguments['tag_ids']))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->values();

        $existing = Tag::whereIn('id', $ids)->pluck('id');
        $missing = $ids->diff($existing);

        if ($missing->isNotEmpty()) {
            return Response::error('Unknown tag id(s): '.$missing->implode(', ').'.');
        }

        $product->tags()->sync($existing->all());

        return Response::json([
            'updated' => true,
            'product_id' => $product->id,
            'tag_ids' => $existing->all(),
        ]);
    }
}
