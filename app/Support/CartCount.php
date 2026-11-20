<?php

namespace App\Support;

use App\Models\Product;

/**
 * Number shown on the header cart badge: each set counts once, plus the units
 * added on their own. (The session keeps every unit, with set contents expanded,
 * so a single DJ set would otherwise show as 10.)
 */
class CartCount
{
    public static function of(array $cart): int
    {
        $items = (array) ($cart['items'] ?? []);
        $bundles = array_filter((array) ($cart['bundles'] ?? []), fn ($q) => (int) $q > 0);

        $covered = [];
        if ($bundles) {
            foreach (Product::with('bundleItems')->whereIn('id', array_keys($bundles))->get() as $bundle) {
                foreach ($bundle->bundleItems as $child) {
                    $covered[$child->id] = ($covered[$child->id] ?? 0) + (int) $child->pivot->amount * (int) $bundles[$bundle->id];
                }
            }
        }

        $standalone = 0;
        foreach ($items as $id => $units) {
            $standalone += max(0, (int) $units - (int) ($covered[$id] ?? 0));
        }

        return (int) array_sum($bundles) + $standalone;
    }
}
