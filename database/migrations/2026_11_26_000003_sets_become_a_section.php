<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;

/**
 * Sety moves out of Zvuk and becomes a section of its own, first in the menu:
 * the sets mix sound and lights, and they are what most customers come for.
 * Does nothing without the category.
 */
return new class extends Migration
{
    private const ORDER = ['sety', 'zvuk', 'svetla', 'stage', 'video', 'pocitace', 'distro'];

    private const OLD_ORDER = ['zvuk', 'svetla', 'stage', 'video', 'pocitace', 'distro'];

    public function up(): void
    {
        if (! Category::where('slug', 'sety')->exists()) {
            return;
        }

        Category::where('slug', 'sety')->update(['parent_id' => null]);
        foreach (self::ORDER as $i => $slug) {
            Category::where('slug', $slug)->update(['position' => $i + 1]);
        }
    }

    public function down(): void
    {
        $zvuk = Category::where('slug', 'zvuk')->first();
        if (! $zvuk) {
            return;
        }

        Category::where('slug', 'sety')->update(['parent_id' => $zvuk->id, 'position' => 1]);
        foreach (self::OLD_ORDER as $i => $slug) {
            Category::where('slug', $slug)->update(['position' => $i + 1]);
        }
    }
};
