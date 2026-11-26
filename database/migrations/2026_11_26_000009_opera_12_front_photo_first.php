<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The Opera 12's gallery had no stored order, so the site showed the back of
 * the speaker first. The front photo goes first. Does nothing without the
 * product or with fewer than two photos.
 */
return new class extends Migration
{
    public function up(): void
    {
        $product = Product::where('slug', 'db-technologies-opera-12')->first();
        $media = $product?->getMedia('product-images');

        if (! $media || $media->count() < 2) {
            return;
        }

        $ordered = $media->sortBy(fn (Media $m) => [
            str_contains(strtolower($m->name.' '.$m->file_name), 'front') ? 0 : 1,
            $m->order_column ?? PHP_INT_MAX,
            $m->id,
        ])->values();

        Media::setNewOrder($ordered->pluck('id')->all());
    }

    public function down(): void
    {
        // Not reversed.
    }
};
