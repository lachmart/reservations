<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Where an old product URL should go: the product's category after a delete,
 * or its new URL after a slug change. Kept up to date by Product model events.
 */
class ProductRedirect extends Model
{
    protected $fillable = ['old_slug', 'target'];

    public static function remember(string $oldSlug, string $target): void
    {
        static::updateOrCreate(['old_slug' => $oldSlug], ['target' => $target]);
    }

    /**
     * Target path for an old slug: hand-picked entries in config/redirects.php
     * win, then the remembered ones.
     */
    public static function targetFor(string $slug): ?string
    {
        return config("redirects.products.{$slug}")
            ?? static::where('old_slug', $slug)->value('target');
    }
}
