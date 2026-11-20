<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The band set uses Nexo PS10R2 tops (the PS15R2 need a Nexo rack that is not in
 * the catalog); the MT18 subs run from the Amprack FP. Does nothing without the
 * live catalog.
 */
return new class extends Migration
{
    private const COMPONENTS = [
        'Nexo PS10R2' => 2,
        'Subwoofer MT18' => 2,
        'Amprack FP' => 1,
        'Gravity SP 5322 B' => 2,
        'SPEAKON (Titanex 2x2,5mm + Neutrik) - 10m' => 4,
    ];

    public function up(): void
    {
        $set = Product::where('slug', 'set-svatba-s-kapelou')->first();
        $components = Product::whereIn('name', array_keys(self::COMPONENTS))->get()->keyBy('name');

        if (! $set || $components->count() !== count(self::COMPONENTS)) {
            return;
        }

        $stock = min(array_map(
            fn ($name, $qty) => intdiv((int) $components[$name]->stock, $qty),
            array_keys(self::COMPONENTS),
            self::COMPONENTS,
        ));

        DB::transaction(function () use ($set, $components, $stock) {
            $set->fill([
                'price_per_day' => 4290,
                'stock' => max(1, $stock),
                'description' => "Ozvučení pro živou kapelu na svatbě, plese nebo oslavě do cca 200 hostů. Dva topy Nexo PS10 na stojnách nad dvěma subwoofery MT18, zesilovačový rack a kabeláž.\n\nHodí se, když kapela vozí vlastní mixpult a potřebuje jen reproduktory. Mixpult a mikrofony rádi přidáme.",
            ])->save();

            $set->bundleItems()->sync(collect(self::COMPONENTS)->mapWithKeys(
                fn ($qty, $name) => [$components[$name]->id => ['amount' => $qty]]
            )->all());
        });
    }

    public function down(): void
    {
        // Not restored; edit the set in the admin if needed.
    }
};
