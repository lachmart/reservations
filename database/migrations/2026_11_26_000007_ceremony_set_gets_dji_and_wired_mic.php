<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The ceremony set swaps the AKG WMS40 handhelds for two DJI Mic 2 lavaliers
 * (the officiants, hands free) plus a wired Shure SM58 with a cable for
 * witnesses or speeches. The price follows the parts: 1 190 − 250 (AKG) + 250
 * (DJI) + 100 (SM58) + 10 (cable) = 1 300 Kč. Does nothing without the set or
 * the new parts.
 */
return new class extends Migration
{
    private const REMOVE = 'AKG WMS40 Mini Dual';

    private const ADD = [
        'DJI Mic 2' => 1,
        'Shure SM58' => 1,
        'XLR - XLR (Sommer cable + Neutrik) - 10m' => 1,
    ];

    private const PRICE = 1300;

    public function up(): void
    {
        $set = Product::where('slug', 'set-svatebni-obrad')->first();
        $parts = Product::whereIn('name', array_keys(self::ADD))->get()->keyBy('name');

        if (! $set || $parts->count() !== count(self::ADD)) {
            return;
        }

        DB::transaction(function () use ($set, $parts) {
            if ($akg = Product::where('name', self::REMOVE)->first()) {
                $set->bundleItems()->detach($akg->id);
            }
            $set->bundleItems()->syncWithoutDetaching(collect(self::ADD)->mapWithKeys(
                fn ($amount, $name) => [$parts[$name]->id => ['amount' => $amount]]
            )->all());

            $stock = $set->bundleItems()->get()->min(
                fn (Product $part) => intdiv((int) $part->stock, max(1, (int) $part->pivot->amount))
            );

            $set->fill([
                'price_per_day' => self::PRICE,
                'stock' => max(1, (int) $stock),
                // Only the sentence about the mics changes; other admin edits stay.
                'description' => str_replace(
                    'Na stativech, s bezdrátovým dvojitým mikrofonem pro oddávajícího a svědky.',
                    'Na stativech, se dvěma bezdrátovými klopovými mikrofony DJI Mic 2 pro oddávající a s drátovým mikrofonem Shure SM58 pro svědky nebo proslovy.',
                    (string) $set->description,
                ),
            ])->save();
        });
    }

    public function down(): void
    {
        // Not restored; edit the set in the admin if needed.
    }
};
