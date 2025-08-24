<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function index(): View
    {
        $products = Product::query()
            ->latest('id')
            ->paginate(12);

        return view('products.index', compact('products'));
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): View
    {
        $start = request()->query('start_date');
        $end = request()->query('end_date');
        $available = null;
        if ($start && $end) {
            $available = $product->availableForRange($start, $end);
        }
        return view('products.show', compact('product', 'available', 'start', 'end'));
    }
}
