<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The band set becomes a complete package: on top of the PS10/MT18 rig it gets
 * a digital mixer, a wireless mic for speeches, four LED lights on two stands
 * and the cables for them. The MT18 went down to 500 Kč/day, so the parts now
 * add up to 6 280 Kč; the set costs 5 690 Kč. Does nothing without the live
 * catalog.
 */
return new class extends Migration
{
    private const PRICE = 5690;

    private const COMPONENTS = [
        'Nexo PS10R2' => 2,
        'Subwoofer MT18' => 2,
        'Amprack FP' => 1,
        'Gravity SP 5322 B' => 2,
        'SPEAKON (Titanex 2x2,5mm + Neutrik) - 10m' => 4,
        'Allen&Heath CQ-20B (rack)' => 1,
        'XLR - XLR (Sommer cable + Neutrik) - 10m' => 2,
        'Sennheiser XSW 2-835 B' => 1,
        'Fractal Lights 9x10W' => 4,
        'Gravity LS TBTV 28' => 2,
        'Titanex 3G1,5 H07RN-F, vidlice Legrand, PES ABB 4.zás, délka 10m' => 2,
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
            // The slug stays, so links to the set keep working.
            $set->fill([
                'name' => 'Set Svatba s kapelou (zvuk, mixpult, světla)',
                'price_per_day' => self::PRICE,
                'stock' => max(1, $stock),
                'description' => "Kompletní ozvučení a světla pro živou kapelu na svatbě, plese nebo oslavě do cca 200 hostů.\n\nV setu jsou dva topy Nexo PS10 na stojnách nad dvěma subwoofery MT18, zesilovačový rack, digitální mixpult Allen&Heath CQ-20B (ovládá se z tabletu nebo notebooku), bezdrátový mikrofon Sennheiser na proslovy a čtyři LED světla na dvou stativech. Kabeláž a prodlužky jsou v ceně.\n\nMikrofony na nástroje, odposlechy nebo notebook s ovládacím softwarem rádi přidáme.",
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
