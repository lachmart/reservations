<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rebuilds the wedding ceremony set as two battery JBL speakers on stands with a
 * dual wireless microphone (works outdoors without a socket). Reuses the existing
 * "Svatební balíček – obřad" product when present, otherwise creates the set.
 * Does nothing on databases without the live catalog.
 */
return new class extends Migration
{
    private const COMPONENTS = [
        'JBL Eon Compact One' => 2,
        'Konig&Meyer 21450 black' => 2,
        'AKG WMS40 Mini Dual' => 1,
    ];

    public function up(): void
    {
        $components = Product::whereIn('name', array_keys(self::COMPONENTS))->get()->keyBy('name');
        if ($components->count() !== count(self::COMPONENTS)) {
            return;
        }

        $set = Product::where('slug', 'set-svatebni-obrad')->first()
            ?? Product::where('slug', 'svatebmi-balicek-obrad')->first()
            ?? Product::where('name', 'Svatební balíček – obřad')->first()
            ?? new Product([
                'status' => $components->first()->status ?? '',
                'category_id' => (Category::where('slug', 'zvuk')->first() ?? $components->first()->category)->id,
            ]);

        $stock = min(array_map(
            fn ($name, $qty) => intdiv((int) $components[$name]->stock, $qty),
            array_keys(self::COMPONENTS),
            self::COMPONENTS,
        ));

        DB::transaction(function () use ($set, $components, $stock) {
            $set->fill([
                'name' => 'Set Svatební obřad (2× JBL na baterii + mikrofony)',
                'slug' => 'set-svatebni-obrad',
                'description' => "Ozvučení svatebního obřadu venku i v obřadní síni do cca 80 hostů. Dvě bedny JBL EON ONE Compact hrají na baterii, takže nepotřebujete zásuvku. Na stativech, s bezdrátovým dvojitým mikrofonem pro oddávajícího a svědky.\n\nHudbu na nástup pustíte z mobilu přes Bluetooth nebo kabelem. Hodí se i na proslovy a přípitek na hostině.",
                'price_per_day' => 1190,
                'stock' => max(1, $stock),
            ])->save();

            $set->bundleItems()->sync(collect(self::COMPONENTS)->mapWithKeys(
                fn ($qty, $name) => [$components[$name]->id => ['amount' => $qty]]
            )->all());
        });
    }

    public function down(): void
    {
        // The previous contents are not restored; edit the set in the admin if needed.
    }
};
