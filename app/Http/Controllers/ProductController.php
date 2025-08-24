<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class ProductController extends Controller
{
    /**
     * Display a listing of the products with category, tag filters, sorting and text search.
     */
    public function index(): View
    {
        $request = request();
        $categoryId = $request->integer('category');
        $tagIds = array_filter((array) $request->input('tags', []), fn($v) => is_numeric($v));
        $sort = $request->input('sort', 'newest');
        $q = trim((string) $request->input('q', ''));

        $query = Product::query()->with(['category', 'tags']);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if (!empty($tagIds)) {
            // any of the selected tags
            $query->whereHas('tags', function (Builder $q2) use ($tagIds) {
                $q2->whereIn('tags.id', $tagIds);
            });
        }

        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->where(function (Builder $q2) use ($like) {
                $q2->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price_per_day', 'asc')->orderBy('id', 'desc');
                break;
            case 'price_desc':
                $query->orderBy('price_per_day', 'desc')->orderBy('id', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'newest':
            default:
                $query->latest('id');
                break;
        }

        $products = $query->paginate(12)->withQueryString();

        $categories = Category::orderBy('name')->get();
        $allTags = Tag::orderBy('name')->get();

        // Return only the list for AJAX requests
        if ($request->ajax()) {
            return view('products._list', [
                'products' => $products,
            ]);
        }

        return view('products.index', [
            'products' => $products,
            'categories' => $categories,
            'allTags' => $allTags,
            'selectedCategory' => $categoryId,
            'selectedTags' => $tagIds,
            'sort' => $sort,
            'q' => $q,
        ]);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): View
    {
        $start = request()->query('start_date');
        $end = request()->query('end_date');

        // If not provided in query, use dates from session cart (remembered across the site)
        if (!$start || !$end) {
            $cart = request()->session()->get('cart', []);
            $start = $start ?: ($cart['start_date'] ?? null);
            $end = $end ?: ($cart['end_date'] ?? null);
        }

        $available = null;
        if ($start && $end) {
            $available = $product->availableForRange($start, $end);
        }
        return view('products.show', compact('product', 'available', 'start', 'end'));
    }
}
