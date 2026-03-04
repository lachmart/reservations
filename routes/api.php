<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\CartController;

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/tags', [TagController::class, 'index']);

Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::post('/dates', [CartController::class, 'setDates']);
    Route::post('/add', [CartController::class, 'add']);
    Route::patch('/{product}', [CartController::class, 'update']);
    Route::delete('/{product}', [CartController::class, 'remove']);
    Route::delete('/', [CartController::class, 'clear']);
    Route::post('/checkout', [CartController::class, 'checkout']);
});
