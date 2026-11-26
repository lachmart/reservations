<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The admin's main-photo field used to save into the "default" media
 * collection while the site and the MCP photo tools use "avatar", so photos
 * added one way were invisible the other way. The field now uses "avatar";
 * this moves main photos that were uploaded through the admin there too.
 *
 * Only products whose photos are all in "default" are touched (the first one
 * becomes the main photo, any others the gallery), so what the site shows
 * stays the same. A "default" photo next to an existing "avatar" one is an
 * old, replaced photo the site never shows; it is left alone. File paths do
 * not depend on the collection, so URLs don't change.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('media')) {
            return;
        }

        $byProduct = DB::table('media')
            ->where('model_type', (new Product)->getMorphClass())
            ->orderBy('order_column')
            ->orderBy('id')
            ->get(['id', 'model_id', 'collection_name'])
            ->groupBy('model_id');

        foreach ($byProduct as $media) {
            $collections = $media->pluck('collection_name')->unique();
            if ($collections->contains('avatar') || $collections->contains('product-images')) {
                continue;
            }

            $default = $media->where('collection_name', 'default')->values();
            if ($default->isEmpty()) {
                continue;
            }

            DB::table('media')->where('id', $default->first()->id)->update(['collection_name' => 'avatar']);
            DB::table('media')->whereIn('id', $default->slice(1)->pluck('id'))->update(['collection_name' => 'product-images']);
        }
    }

    public function down(): void
    {
        // Not reversed: "avatar" is the right place for the main photo.
    }
};
