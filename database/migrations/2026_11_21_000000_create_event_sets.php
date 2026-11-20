<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ready-made sets for typical events, built from the live catalog.
 *
 * Components are matched by their exact catalog names, so the migration does
 * nothing on databases without that catalog (local, tests). Sets that already
 * exist (by slug) are left alone, so it is safe to re-run. It also gives every
 * product without a slug one, so no product page 404s.
 */
return new class extends Migration
{
    private const SETS = [
        [
            'slug' => 'set-svatba-s-kapelou',
            'name' => 'Set Svatba s kapelou (2× top + 2× sub)',
            'price_per_day' => 4990,
            'description' => "Ozvučení pro živou kapelu na svatbě, plese nebo oslavě do cca 250 hostů. Dva výkonné topy Nexo na stojnách nad dvěma subwoofery, zesilovačový rack a kabeláž.\n\nHodí se, když kapela vozí vlastní mixpult a potřebuje jen reproduktory. Mixpult a mikrofony rádi přidáme.",
            'components' => [
                'Nexo PS15R2 (2000W, 1x15"+1x3")' => 2,
                'Subwoofer MT18' => 2,
                'Amprack FP' => 1,
                'Gravity SP 5322 B' => 2,
                'SPEAKON (Titanex 2x2,5mm + Neutrik) - 10m' => 4,
            ],
        ],
        [
            'slug' => 'set-dj-party',
            'name' => 'Set DJ párty (2 bedny + světla)',
            'price_per_day' => 1290,
            'description' => "Pro DJ na oslavě, svatební zábavě nebo firemním večírku do cca 100 hostů. Dvě aktivní 15\" bedny dB Technologies na stativech a dvě LED světla na stativu, včetně kabelů a prodlužky.\n\nDJ připojí svůj kontrolér přímo do beden.",
            'components' => [
                'dB Technologies Opera 15' => 2,
                'Konig&Meyer 21450 black' => 2,
                'Fractal Lights 9x10W' => 2,
                'Gravity LS TBTV 28' => 1,
                'XLR - XLR (Sommer cable + Neutrik) - 10m' => 2,
                'Titanex 3G1,5 H07RN-F, vidlice Legrand, PES ABB 4.zás, délka 10m' => 1,
            ],
        ],
        [
            'slug' => 'set-proslovy-a-hudba',
            'name' => 'Set Proslovy a hudba (bedna + bezdrátový mikrofon)',
            'price_per_day' => 950,
            'description' => "Nejmenší set na proslovy, přípitek a hudbu z mobilu nebo notebooku. Hodí se na raut, rodinnou oslavu, firemní akci nebo menší obřad do cca 60 hostů.\n\nAktivní bedna na stativu, bezdrátový mikrofon, malý mixpult a kabely.",
            'components' => [
                'dB Technologies Opera 12' => 1,
                'Konig&Meyer 21450 black' => 1,
                'Sennheiser XSW 2-835 B' => 1,
                'Allen&Heath ZED6' => 1,
                'XLR - XLR (Sommer cable + Neutrik) - 10m' => 1,
                'Titanex 3G1,5 H07RN-F, vidlice Legrand, PES ABB 4.zás, délka 10m' => 1,
            ],
        ],
    ];

    public function up(): void
    {
        // Products without a slug have no reachable page.
        Product::whereNull('slug')->orWhere('slug', '')->get()->each->save();

        foreach (self::SETS as $set) {
            if (Product::where('slug', $set['slug'])->exists()) {
                continue;
            }

            $components = Product::whereIn('name', array_keys($set['components']))->get()->keyBy('name');
            if ($components->count() !== count($set['components'])) {
                continue; // catalog not present (local/test database)
            }

            $category = Category::where('slug', 'zvuk')->first()
                ?? $components->first()->category;

            // As many sets as the components allow at once.
            $stock = min(array_map(
                fn ($name, $qty) => intdiv((int) $components[$name]->stock, $qty),
                array_keys($set['components']),
                $set['components'],
            ));

            DB::transaction(function () use ($set, $components, $category, $stock) {
                $bundle = Product::create([
                    'name' => $set['name'],
                    'slug' => $set['slug'],
                    'description' => $set['description'],
                    'status' => $components->first()->status ?? '',
                    'category_id' => $category->id,
                    'price_per_day' => $set['price_per_day'],
                    'stock' => max(1, $stock),
                ]);

                foreach ($set['components'] as $name => $qty) {
                    $bundle->bundleItems()->attach($components[$name]->id, ['amount' => $qty]);
                }
            });
        }
    }

    public function down(): void
    {
        $ids = Product::whereIn('slug', array_column(self::SETS, 'slug'))->pluck('id');
        DB::table('bundle_items')->whereIn('bundle_product_id', $ids)->delete();
        Product::whereIn('id', $ids)->delete();
    }
};
