<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::get('/products', [ProductController::class, 'index'])
    ->name('products.index');

Route::get('/products/create', [ProductController::class, 'create'])
    ->name('products.create');

Route::post('/products', [ProductController::class, 'store'])
    ->name('products.store');

Route::get('/products/suggestions', [ProductController::class, 'suggestions'])
    ->name('products.suggestions');

/*
|--------------------------------------------------------------------------
| Product Statistics
|--------------------------------------------------------------------------
*/

Route::get('/products/statistics', [ProductController::class, 'statistics'])
    ->name('products.statistics');

/*
|--------------------------------------------------------------------------
| Product CSV Export
|--------------------------------------------------------------------------
|
| Keep this BEFORE /products/{product}/edit.
|
*/

Route::get('/products/export/csv', [ProductController::class, 'exportCsv'])
    ->name('products.export.csv');

/*
|--------------------------------------------------------------------------
| Edit / Update / Delete
|--------------------------------------------------------------------------
*/

Route::get('/products/{product}/edit', [ProductController::class, 'edit'])
    ->name('products.edit');

Route::put('/products/{product}', [ProductController::class, 'update'])
    ->name('products.update');

Route::delete('/products/{product}', [ProductController::class, 'destroy'])
    ->name('products.destroy');