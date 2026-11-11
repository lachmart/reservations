<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        // Flagship gear first: products that have a photo, then most valuable.
        $featured = Product::query()
            ->available()
            ->with(['category', 'media'])
            ->withCount('media')
            ->orderByDesc('media_count')
            ->orderByDesc('price_per_day')
            ->limit(8)
            ->get();

        // The real sections, with a representative photo for each tile.
        $categories = Category::query()
            ->withCount(['products as available_count' => fn ($q) => $q->available()])
            ->having('available_count', '>', 0)
            ->orderByDesc('available_count')
            ->get()
            ->map(function (Category $c) {
                $c->sample_images = $this->categoryImages($c);
                $c->sample_image = $c->sample_images[0] ?? null;

                return $c;
            });

        $productCount = Product::available()->count();

        return view('home', compact('featured', 'categories', 'productCount'));
    }

    /**
     * Hand-picked products that best show off each category on the homepage tiles
     * (first = the large hero image). Missing or photo-less slugs are skipped.
     */
    private const CATEGORY_SHOWCASE = [
        'zvuk' => ['nexo-ps15r2-2000w-1x151x3', 'allenheath-sq5-case', 'shure-sm58'],
        'svetla' => ['cameo-thunderwash-600-rgbw', 'varytec-theatre-spot-6501000-fresnel', 'chauvet-proffesional-amhaze-whisper-2000w'],
    ];

    /**
     * Up to three photo URLs for a category tile: the curated showcase first, then
     * topped up with the most valuable photographed products of distinct kinds
     * (so we don't show three microphones or seven lengths of the same cable).
     *
     * @return list<string>
     */
    private function categoryImages(Category $category): array
    {
        $products = Product::where('category_id', $category->id)
            ->available()
            ->with(['media', 'category'])
            ->orderByDesc('price_per_day')
            ->get()
            ->filter(fn (Product $p) => $p->mainImageUrl());

        $showcase = self::CATEGORY_SHOWCASE[$category->slug] ?? [];
        $ordered = $products->sortBy(function (Product $p) use ($showcase) {
            $pos = array_search($p->slug, $showcase, true);

            return $pos === false ? count($showcase) : $pos;
        })->values();

        $urls = [];
        $kinds = [];
        foreach ($ordered as $p) {
            $kind = $p->placeholderKind();
            if (! in_array($p->slug, $showcase, true) && in_array($kind, $kinds, true)) {
                continue;
            }
            $urls[] = $p->mainImageUrl();
            $kinds[] = $kind;
            if (count($urls) === 3) {
                break;
            }
        }

        return $urls;
    }
}
