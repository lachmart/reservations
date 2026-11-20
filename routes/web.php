<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\McpDiscoveryController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MaintenanceController;
use App\Support\Maintenance;

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
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

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

// Server housekeeping without SSH (deploy-ftp.sh); token-protected, 404 if unset.
Route::post('/_maintenance/{task}', MaintenanceController::class)
    ->whereIn('task', array_keys(Maintenance::TASKS))
    ->middleware('throttle:10,1')
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

// Landing pages for common searches (config/landing.php). Constrained to the
// configured slugs so they can't shadow other routes.
Route::get('/{landing}', [LandingController::class, 'show'])
    ->whereIn('landing', array_keys(config('landing.pages', [])))
    ->name('landing.show');
