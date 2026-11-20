<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;

/**
 * Landing pages for common searches ("ozvučení svatby Olomouc"…), driven by
 * config/landing.php.
 */
class LandingController extends Controller
{
    public function show(string $landing): View
    {
        $page = config("landing.pages.{$landing}");
        abort_unless(is_array($page), 404);

        $sets = $this->ordered(
            Product::query()->available()->with(['category', 'media', 'bundleItems.media'])
                ->whereIn('slug', $page['sets'] ?? [])->get(),
            'slug',
            $page['sets'] ?? [],
        );

        $products = $this->ordered(
            Product::query()->available()->with(['category', 'media'])
                ->whereIn('name', $page['products'] ?? [])->get(),
            'name',
            $page['products'] ?? [],
        );

        $others = collect(config('landing.pages'))
            ->except($landing)
            ->map(fn ($p, $slug) => ['url' => url($slug), 'label' => $p['nav_label']]);

        return view('landing', compact('page', 'landing', 'sets', 'products', 'others'));
    }

    /** Keep the order given in the config. */
    private function ordered($items, string $key, array $order)
    {
        $pos = array_flip($order);

        return $items->sortBy(fn ($p) => $pos[$p->{$key}] ?? PHP_INT_MAX)->values();
    }
}
