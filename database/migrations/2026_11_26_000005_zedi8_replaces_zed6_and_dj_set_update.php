<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The small Allen&Heath mixer in stock is a ZEDi-8, not a ZED6 (a typo on the
 * site): the product is renamed (its old URL redirects to the new one). The DJ
 * set gets it, so the DJ plugs into the mixer and a mic can go in too, and it
 * no longer takes a light stand (the band set needs both). The price follows
 * the parts: 1 290 + 200 for the mixer − 100 for the stand. Does nothing
 * without the mixer or the set.
 */
return new class extends Migration
{
    private const NAME = 'Allen&Heath ZEDi-8';

    private const SLUG = 'allenheath-zedi-8';

    private const LIGHT_STAND = 'Gravity LS TBTV 28';

    private const PRICE = 1390;

    public function up(): void
    {
        $mixer = Product::whereIn('slug', ['allenheath-zed6', self::SLUG])->first();
        $set = Product::where('slug', 'set-dj-party')->first();

        if (! $mixer || ! $set) {
            return;
        }

        DB::transaction(function () use ($mixer, $set) {
            $mixer->fill([
                'name' => self::NAME,
                'slug' => self::SLUG,
                'description' => str_replace('ZED6', 'ZEDi-8', (string) $mixer->description),
            ])->save();

            $set->bundleItems()->syncWithoutDetaching([$mixer->id => ['amount' => 1]]);
            $stand = Product::where('name', self::LIGHT_STAND)->first();
            if ($stand) {
                $set->bundleItems()->detach($stand->id);
            }

            $stock = $set->bundleItems()->get()->min(
                fn (Product $part) => intdiv((int) $part->stock, max(1, (int) $part->pivot->amount))
            );

            $set->fill([
                'name' => 'Set DJ párty (2 bedny, mixpult, světla)',
                'price_per_day' => self::PRICE,
                'stock' => max(1, (int) $stock),
                // Only the sentences about the gear change; other admin edits stay.
                'description' => str_replace(
                    [
                        'bedny dB Technologies na stativech a dvě LED světla na stativu,',
                        'DJ připojí svůj kontrolér přímo do beden.',
                    ],
                    [
                        'bedny dB Technologies na stativech, mixpult Allen&Heath ZEDi-8 a dvě LED světla,',
                        'DJ připojí kontrolér do mixpultu a zbudou vstupy i na mikrofon nebo přehrávač.',
                    ],
                    (string) $set->description,
                ),
            ])->save();
        });
    }

    public function down(): void
    {
        // Not restored; edit the products in the admin if needed.
    }
};
