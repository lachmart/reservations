<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * Display a listing of the products with category, tag filters, sorting and text search.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $categoryId = $request->integer('category');
        $tagIds = array_filter((array) $request->input('tags', []), fn($v) => is_numeric($v));
        $sort = $request->input('sort', 'newest');
        $q = trim((string) $request->input('q', ''));

        $query = Product::query()->available()->with(['category', 'tags']);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if (!empty($tagIds)) {
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

        return ProductResource::collection($products);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): ProductResource
    {
        $product->load(['category', 'tags']);

        return new ProductResource($product);
    }
}
