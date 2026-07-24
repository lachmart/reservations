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
                $sample = Product::where('category_id', $c->id)
                    ->available()
                    ->withCount('media')
                    ->orderByDesc('media_count')
                    ->orderByDesc('price_per_day')
                    ->first();

                $c->sample_image = $sample ? $sample->mainImageUrl() : null;

                return $c;
            });

        $productCount = Product::available()->count();

        return view('home', compact('featured', 'categories', 'productCount'));
    }
}
