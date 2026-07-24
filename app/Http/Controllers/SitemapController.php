<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [];

        $urls[] = ['loc' => url('/'), 'priority' => '1.0', 'changefreq' => 'weekly'];
        $urls[] = ['loc' => route('products.index'), 'priority' => '0.9', 'changefreq' => 'daily'];
        $urls[] = ['loc' => route('ai.index'), 'priority' => '0.5', 'changefreq' => 'monthly'];

        Category::query()
            ->whereHas('products', fn ($q) => $q->available())
            ->get()
            ->each(function (Category $c) use (&$urls) {
                $urls[] = [
                    'loc' => route('products.index', ['category' => $c->slug]),
                    'priority' => '0.7',
                    'changefreq' => 'weekly',
                ];
            });

        Product::query()
            ->available()
            ->orderByDesc('id')
            ->get()
            ->each(function (Product $p) use (&$urls) {
                $urls[] = [
                    'loc' => route('products.show', $p),
                    'priority' => '0.8',
                    'changefreq' => 'weekly',
                    'lastmod' => optional($p->updated_at)->toAtomString(),
                ];
            });

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
