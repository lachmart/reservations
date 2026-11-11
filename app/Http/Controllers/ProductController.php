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
        // Category filter accepts a slug (nice URL) or a numeric id.
        $categoryParam = trim((string) $request->input('category', ''));
        $category = $categoryParam === ''
            ? null
            : (ctype_digit($categoryParam)
                ? Category::find($categoryParam)
                : Category::where('slug', $categoryParam)->first());
        $categoryId = $category?->id;
        $tagIds = array_filter((array) $request->input('tags', []), fn($v) => is_numeric($v));
        $sort = $request->input('sort', 'featured');
        $q = trim((string) $request->input('q', ''));

        // Rental term: if provided (from the term widget) remember it site-wide.
        $start = $request->input('start_date');
        $end = $request->input('end_date');
        if ($start && $end && strtotime($start) !== false && strtotime($end) !== false && strtotime($end) > strtotime($start)) {
            $cart = $request->session()->get('cart', []);
            $cart['start_date'] = $start;
            $cart['end_date'] = $end;
            $request->session()->put('cart', $cart);
        }
        $cart = $request->session()->get('cart', []);
        $termStart = $cart['start_date'] ?? $start;
        $termEnd = $cart['end_date'] ?? $end;

        $query = Product::query()->available()->with(['category', 'tags'])->withCount('media');

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
                $query->latest('id');
                break;
            case 'featured':
            default:
                // Flagship-first: gear with a photo, then most valuable.
                $query->orderByDesc('media_count')->orderByDesc('price_per_day')->orderBy('id', 'desc');
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
            'selectedCategory' => $category?->slug,
            'selectedTags' => $tagIds,
            'sort' => $sort,
            'q' => $q,
            'termStart' => $termStart,
            'termEnd' => $termEnd,
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
            // If the product is a bundle, its availability is limited by its components
            if (method_exists($product, 'isBundle') && $product->isBundle()) {
                $possibleCounts = [];
                foreach ($product->bundleItems as $child) {
                    $availableChild = $child->availableForRange($start, $end);
                    $perBundle = max(1, (int) $child->pivot->amount);
                    // IMPORTANT: Do not subtract items already in the user's cart here.
                    $remainingBundles = intdiv(max(0, $availableChild), $perBundle);
                    $possibleCounts[] = $remainingBundles;
                }
                $available = !empty($possibleCounts) ? min($possibleCounts) : 0;
            } else {
                $available = $product->availableForRange($start, $end);
            }
        }
        return view('products.show', compact('product', 'available', 'start', 'end'));
    }
}
