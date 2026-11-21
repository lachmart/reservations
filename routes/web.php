<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\McpDiscoveryController;
use App\Http\Controllers\LandingController;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::view('/ai', 'ai')->name('ai.index');
Route::get('/sitemap.xml', [SitemapController::class, 'index']);

// MCP discovery (server card). The /.well-known paths are blocked by the hosting
// today, so the same card is also served from /mcp/server-card.json.
foreach (['/.well-known/mcp', '/.well-known/mcp.json', '/.well-known/mcp/server-card.json', '/mcp/server-card.json'] as $path) {
    Route::get($path, [McpDiscoveryController::class, 'card']);
}

Route::post('/poptavka', [EnquiryController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('enquiry.store');

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])
    ->name('products.show')
    // A product that no longer exists: 301 to where it went (renamed) or its
    // category (deleted), see App\Models\ProductRedirect; otherwise 404.
    ->missing(function (\Illuminate\Http\Request $request) {
        $target = \App\Models\ProductRedirect::targetFor((string) $request->route('product'));
        abort_unless($target, 404);

        return redirect($target, 301);
    });

// Cart routes
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/dates', [CartController::class, 'setDates'])->name('cart.setDates');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{product}', [CartController::class, 'remove'])->name('cart.remove');
// Bundle-specific routes
Route::patch('/cart/bundle/{product}', [CartController::class, 'updateBundle'])->name('cart.updateBundle');
Route::delete('/cart/bundle/{product}', [CartController::class, 'removeBundle'])->name('cart.removeBundle');
Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');
Route::post('/cart/checkout', [CartController::class, 'checkout'])
    ->middleware('throttle:10,10')
    ->name('cart.checkout');
Route::get('/thank-you', [CartController::class, 'thankyou'])->name('cart.thankyou');

// Landing pages for common searches (config/landing.php). Constrained to the
// configured slugs so they can't shadow other routes.
Route::get('/{landing}', [LandingController::class, 'show'])
    ->whereIn('landing', array_keys(config('landing.pages', [])))
    ->name('landing.show');
