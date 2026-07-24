<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Mcp\Server\Tools\Annotations\Title;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[Title('Attach product photo from URL')]
class AttachProductPhoto extends BaseTool
{
    public function description(): string
    {
        return 'Download an image from a public http(s) URL and attach it to a product via the '
            .'media library. Defaults to the product\'s main image ("avatar" collection) and '
            .'replaces any existing image there. Follows redirects and verifies the response is '
            .'actually an image.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->integer('product_id')->description('The product to attach the photo to.')->required()
            ->string('image_url')->description('Public http(s) URL of the image to download.')->required()
            ->string('collection')->description('Collection: "avatar" (main image, default) or "product-images" (gallery).')
            ->boolean('replace')->description('Clear existing images in the collection first (default true).');
    }

    protected function run(array $arguments): ToolResult
    {
        $validator = Validator::make($arguments, [
            'product_id' => ['required', 'integer'],
            'image_url' => ['required', 'url', 'max:2048'],
            'collection' => ['nullable', 'string', 'in:avatar,product-images'],
            'replace' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return ToolResult::error($validator->errors()->first());
        }

        $product = Product::find($arguments['product_id']);

        if (! $product) {
            return ToolResult::error("No product found with id {$arguments['product_id']}.");
        }

        $collection = $arguments['collection'] ?? 'avatar';
        $replace = $arguments['replace'] ?? true;

        // Download with a browser-like User-Agent (many hosts block default PHP UA)
        // and follow redirects (picsum, CDNs, etc.).
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; MarakaRentalBot/1.0)',
            'Accept' => 'image/avif,image/webp,image/png,image/jpeg,*/*',
        ])->timeout(25)->get($arguments['image_url']);

        if (! $response->successful()) {
            return ToolResult::error("Could not download image (HTTP {$response->status()}) from {$arguments['image_url']}.");
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        if (! str_starts_with($contentType, 'image/')) {
            return ToolResult::error("URL did not return an image (Content-Type: {$contentType}).");
        }

        $extMap = ['image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/png' => 'png',
            'image/webp' => 'webp', 'image/gif' => 'gif', 'image/avif' => 'avif'];
        $ext = $extMap[explode(';', $contentType)[0]] ?? 'jpg';

        $tmp = tempnam(sys_get_temp_dir(), 'mcpimg_').'.'.$ext;
        file_put_contents($tmp, $response->body());

        if ($replace) {
            $product->clearMediaCollection($collection);
        }

        $media = $product->addMedia($tmp)
            ->usingFileName(Str::slug($product->name).'-'.Str::random(6).'.'.$ext)
            ->toMediaCollection($collection);

        return ToolResult::json([
            'attached' => true,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'collection' => $collection,
            'media_id' => $media->id,
            'file_name' => $media->file_name,
            'url' => $media->getUrl(),
        ]);
    }
}
