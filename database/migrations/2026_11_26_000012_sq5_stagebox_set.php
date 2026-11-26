<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A digital desk set: the Allen&Heath SQ5 in its case, the stagebox (AR2412)
 * and the 50 m CAT5 cable that links them. Parts 3 100 Kč, the set 2 790 Kč.
 * Does nothing without the catalog; safe to re-run.
 */
return new class extends Migration
{
    private const SLUG = 'set-mixpult-sq5-stagebox';

    private const COMPONENTS = [
        'Allen&Heath SQ5 + case' => 1,
        'Allen&Heath Stagebox' => 1,
        'CAT 5 - 50m' => 1,
    ];

    public function up(): void
    {
        $sety = Category::where('slug', 'sety')->first();
        $components = Product::whereIn('name', array_keys(self::COMPONENTS))->get()->keyBy('name');

        if (! $sety || $components->count() !== count(self::COMPONENTS)) {
            return;
        }

        DB::transaction(function () use ($sety, $components) {
            $stock = min(array_map(
                fn ($name, $qty) => intdiv((int) $components[$name]->stock, $qty),
                array_keys(self::COMPONENTS),
                self::COMPONENTS,
            ));

            $set = Product::firstOrCreate(['slug' => self::SLUG], [
                'name' => 'Set Mixpult SQ5 + stagebox',
                'description' => "Digitální mixpult Allen&Heath SQ5 v case a stagebox s Allen&Heath AR2412 (24 vstupů, 12 výstupů), propojené jedním 50m kabelem CAT5. Mikrofony a nástroje zapojíte do stageboxu na pódiu a k mixpultu vede jen jeden kabel.\n\nMikrofony, kabely, odposlechy nebo notebook s ovládacím softwarem rádi přidáme.",
                'category_id' => $sety->id,
                'price_per_day' => 2790,
                'stock' => max(1, $stock),
            ]);

            $set->bundleItems()->syncWithoutDetaching(collect(self::COMPONENTS)->mapWithKeys(
                fn ($qty, $name) => [$components[$name]->id => ['amount' => $qty]]
            )->all());
        });
    }

    public function down(): void
    {
        $set = Product::where('slug', self::SLUG)->first();
        if ($set) {
            $set->bundleItems()->detach();
            $set->delete();
        }
    }
};
