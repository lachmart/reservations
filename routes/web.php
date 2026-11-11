<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SitemapController;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::view('/ai', 'ai')->name('ai.index');
Route::get('/sitemap.xml', [SitemapController::class, 'index']);

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
Route::post('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
Route::get('/thank-you', [CartController::class, 'thankyou'])->name('cart.thankyou');
