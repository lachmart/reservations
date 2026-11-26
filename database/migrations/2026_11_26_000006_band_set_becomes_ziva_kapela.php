<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

/**
 * "Svatba s kapelou" read as wedding-only, but the set is for any event with
 * a live band: a ball, a company party, a celebration. Renamed to "Živá
 * kapela"; the old URL redirects to the new one. Does nothing without the set.
 */
return new class extends Migration
{
    public function up(): void
    {
        $set = Product::whereIn('slug', ['set-svatba-s-kapelou', 'set-ziva-kapela'])->first();
        if (! $set) {
            return;
        }

        $set->fill([
            'name' => 'Set Živá kapela (zvuk, mixpult, světla)',
            'slug' => 'set-ziva-kapela',
            'description' => str_replace(
                'na svatbě, plese nebo oslavě',
                'na svatbě, plese, firemním večírku nebo oslavě',
                (string) $set->description,
            ),
        ])->save();
    }

    public function down(): void
    {
        $set = Product::where('slug', 'set-ziva-kapela')->first();
        if (! $set) {
            return;
        }

        $set->fill([
            'name' => 'Set Svatba s kapelou (zvuk, mixpult, světla)',
            'slug' => 'set-svatba-s-kapelou',
            'description' => str_replace(
                'na svatbě, plese, firemním večírku nebo oslavě',
                'na svatbě, plese nebo oslavě',
                (string) $set->description,
            ),
        ])->save();
    }
};
