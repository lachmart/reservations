<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The PS15R2 tops need the Nexo rack, which is now in the catalog (1 000 Kč),
 * and with it a big set: two PS15R2, four MT18 subwoofers, the Nexo rack, the
 * FP amp rack and speakon cables. Parts 7 060 Kč, the set 6 490 Kč (570 Kč
 * off, like the band set). Does nothing without the catalog; safe to re-run.
 */
return new class extends Migration
{
    private const RACK = 'Nexo rack';

    private const SET_SLUG = 'set-velky-zvuk-nexo';

    private const COMPONENTS = [
        'Nexo PS15R2 (2000W, 1x15"+1x3")' => 2,
        'Subwoofer MT18' => 4,
        self::RACK => 1,
        'Amprack FP' => 1,
        'SPEAKON (Titanex 2x2,5mm + Neutrik) - 10m' => 4,
        'SPEAKON (Titanex 2x2,5mm + Neutrik) - 5m' => 2,
    ];

    public function up(): void
    {
        $amps = Category::where('slug', 'zesilovace')->first();
        $sety = Category::where('slug', 'sety')->first();
        $others = Product::whereIn('name', array_keys(self::COMPONENTS))->where('name', '!=', self::RACK)->count();

        if (! $amps || ! $sety || $others !== count(self::COMPONENTS) - 1) {
            return;
        }

        DB::transaction(function () use ($amps, $sety) {
            Product::firstOrCreate(['name' => self::RACK], [
                'description' => "Obsahuje: NEXO DTD-TN TD, Lab.gruppen FP10000Q, patch panel. Vše v jednom racku 4U.\n\nTopy Nexo PS15R2 bez něj nehrají, půjčujeme ho hlavně v setu Velký zvuk Nexo.",
                'category_id' => $amps->id,
                'price_per_day' => 1000,
                'stock' => 1,
            ]);

            $components = Product::whereIn('name', array_keys(self::COMPONENTS))->get()->keyBy('name');
            $stock = min(array_map(
                fn ($name, $qty) => intdiv((int) $components[$name]->stock, $qty),
                array_keys(self::COMPONENTS),
                self::COMPONENTS,
            ));

            $set = Product::firstOrCreate(['slug' => self::SET_SLUG], [
                'name' => 'Set Velký zvuk Nexo (2× PS15, 4× MT18)',
                'description' => "Výkonné ozvučení Nexo pro koncert, ples nebo firemní akci ve velkém sále či stanu.\n\nV setu jsou dva topy Nexo PS15R2, čtyři subwoofery MT18, Nexo rack, zesilovačový rack a kabeláž speakon.\n\nMixpult, mikrofony, stativy nebo světla rádi přidáme.",
                'category_id' => $sety->id,
                'price_per_day' => 6490,
                'stock' => max(1, $stock),
            ]);

            $set->bundleItems()->syncWithoutDetaching(collect(self::COMPONENTS)->mapWithKeys(
                fn ($qty, $name) => [$components[$name]->id => ['amount' => $qty]]
            )->all());
        });
    }

    public function down(): void
    {
        $set = Product::where('slug', self::SET_SLUG)->first();
        if ($set) {
            $set->bundleItems()->detach();
            $set->delete();
        }
    }
};
