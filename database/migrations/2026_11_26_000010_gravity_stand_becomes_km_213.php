<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

/**
 * The crank speaker stands in stock are K&M 213, not Gravity SP 5322 B. The
 * product is renamed in place (same stock and price, the Živá kapela set keeps
 * it; the old URL redirects). Does nothing without the product.
 */
return new class extends Migration
{
    public function up(): void
    {
        $stand = Product::whereIn('slug', ['gravity-sp-5322-b', 'konigmeyer-213'])->first();
        if (! $stand) {
            return;
        }

        $stand->fill([
            'name' => 'Konig&Meyer 213',
            'slug' => 'konigmeyer-213',
            'description' => '<p>Reproduktorový stativ s klikou: i těžší bednu vytočíte do výšky bez námahy.</p><p>Výška: 138–218 cm<br>Průměr tyče: 35 mm<br>Ocel, trojnožka</p>',
        ])->save();
    }

    public function down(): void
    {
        $stand = Product::where('slug', 'konigmeyer-213')->first();
        $stand?->fill(['name' => 'Gravity SP 5322 B', 'slug' => 'gravity-sp-5322-b'])->save();
    }
};
