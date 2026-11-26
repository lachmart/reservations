<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * More light for the band set: two warm-white Varytec LED Studio 150 spots to
 * light the band, and six Fractal lights instead of four, all on the set's two
 * stands. The price goes up by what the extra lights cost (700 Kč), so the set
 * stays 590 Kč below its parts (6 980 Kč). Does nothing without the set or the
 * lights.
 */
return new class extends Migration
{
    private const LIGHTS = [
        'Varytec LED Studio 150 2900K' => 2,
        'Fractal Lights 9x10W' => 6,
    ];

    private const PRICE = 6390;

    public function up(): void
    {
        $set = Product::where('slug', 'set-svatba-s-kapelou')->first();
        $lights = Product::whereIn('name', array_keys(self::LIGHTS))->get()->keyBy('name');

        if (! $set || $lights->count() !== count(self::LIGHTS)) {
            return;
        }

        DB::transaction(function () use ($set, $lights) {
            $set->bundleItems()->syncWithoutDetaching(collect(self::LIGHTS)->mapWithKeys(
                fn ($amount, $name) => [$lights[$name]->id => ['amount' => $amount]]
            )->all());

            $stock = $set->bundleItems()->get()->min(
                fn (Product $part) => intdiv((int) $part->stock, max(1, (int) $part->pivot->amount))
            );

            // Only the sentence about the lights changes; other admin edits stay.
            $description = str_replace(
                ' a čtyři LED světla na dvou stativech.',
                ', šest LED světel Fractal a dva reflektory Varytec s teplým bílým světlem na nasvícení kapely, vše na dvou stativech.',
                (string) $set->description,
            );

            $set->fill([
                'price_per_day' => self::PRICE,
                'stock' => max(1, (int) $stock),
                'description' => $description,
            ])->save();
        });
    }

    public function down(): void
    {
        // Not restored; edit the set in the admin if needed.
    }
};
