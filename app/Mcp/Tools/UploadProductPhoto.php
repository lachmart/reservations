<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Mcp\Server\Attributes\Title;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;

#[Title('Upload product photo (file data)')]
class UploadProductPhoto extends BaseTool
{
    public function description(): string
    {
        return 'Attach a photo to a product from raw base64-encoded image data (i.e. an uploaded '
            .'file, not a URL). Accepts a plain base64 string or a data URL '
            .'("data:image/png;base64,…"). Defaults to the main image ("avatar") and replaces '
            .'any existing image there. Use attach-product-photo instead when you have a URL.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'product_id' => $schema->integer()
                ->description('The product to attach the photo to.')
                ->required(),
            'image_base64' => $schema->string()
                ->description('Base64 image data, optionally a "data:<mime>;base64,…" URL.')
                ->required(),
            'file_name' => $schema->string()
                ->description('Optional file name (extension is corrected to match the image type).'),
            'collection' => $schema->string()
                ->description('Collection: "avatar" (main image, default) or "product-images" (gallery).'),
            'replace' => $schema->boolean()
                ->description('Clear existing images in the collection first (default true).'),
        ];
    }

    protected function run(array $arguments): Response
    {
        $validator = Validator::make($arguments, [
            'product_id' => ['required', 'integer'],
            'image_base64' => ['required', 'string'],
            'file_name' => ['nullable', 'string', 'max:255'],
            'collection' => ['nullable', 'string', 'in:avatar,product-images'],
            'replace' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->first());
        }

        $product = Product::find($arguments['product_id']);

        if (! $product) {
            return Response::error("No product found with id {$arguments['product_id']}.");
        }

        // Strip an optional data-URL prefix, then decode strictly.
        $raw = preg_replace('#^data:[^;]+;base64,#', '', trim($arguments['image_base64']));
        $bytes = base64_decode($raw, true);

        if ($bytes === false || $bytes === '') {
            return Response::error('image_base64 is not valid base64 data.');
        }

        if (strlen($bytes) > 15 * 1024 * 1024) {
            return Response::error('Image is larger than the 15 MB limit.');
        }

        // Detect the real mime from the bytes — don't trust the caller.
        $mime = strtolower((string) (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes));
        $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
            'image/gif' => 'gif', 'image/avif' => 'avif'];

        if (! isset($extMap[$mime])) {
            return Response::error("Decoded data is not a supported image (detected: {$mime}).");
        }

        $ext = $extMap[$mime];
        $collection = $arguments['collection'] ?? 'avatar';
        $replace = $arguments['replace'] ?? true;

        $base = ! empty($arguments['file_name'])
            ? Str::slug(pathinfo($arguments['file_name'], PATHINFO_FILENAME))
            : Str::slug($product->name);
        $fileName = ($base ?: 'photo').'-'.Str::random(6).'.'.$ext;

        $tmp = tempnam(sys_get_temp_dir(), 'mcpupl_').'.'.$ext;
        file_put_contents($tmp, $bytes);

        if ($replace) {
            $product->clearMediaCollection($collection);
        }

        $media = $product->addMedia($tmp)
            ->usingFileName($fileName)
            ->toMediaCollection($collection);

        return Response::json([
            'uploaded' => true,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'collection' => $collection,
            'media_id' => $media->id,
            'file_name' => $media->file_name,
            'size_bytes' => strlen($bytes),
            'url' => $media->getUrl(),
        ]);
    }
}
