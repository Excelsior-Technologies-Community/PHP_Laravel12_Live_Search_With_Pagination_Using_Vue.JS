<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::get(
    '/products',
    [ProductController::class, 'index']
)->name('products.index');

Route::get(
    '/products/create',
    [ProductController::class, 'create']
)->name('products.create');

Route::post(
    '/products',
    [ProductController::class, 'store']
)->name('products.store');

/*
|--------------------------------------------------------------------------
| Suggestions
|--------------------------------------------------------------------------
*/

Route::get(
    '/products/suggestions',
    [ProductController::class, 'suggestions']
)->name('products.suggestions');

/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

Route::get(
    '/products/statistics',
    [ProductController::class, 'statistics']
)->name('products.statistics');

/*
|--------------------------------------------------------------------------
| CSV Export
|--------------------------------------------------------------------------
*/

Route::get(
    '/products/export/csv',
    [ProductController::class, 'exportCsv']
)->name('products.export.csv');

/*
|--------------------------------------------------------------------------
| Bulk Operations
|--------------------------------------------------------------------------
*/

Route::post(
    '/products/bulk-delete',
    [ProductController::class, 'bulkDelete']
)->name('products.bulk-delete');

Route::post(
    '/products/bulk-activate',
    [ProductController::class, 'bulkActivate']
)->name('products.bulk-activate');

Route::post(
    '/products/bulk-deactivate',
    [ProductController::class, 'bulkDeactivate']
)->name('products.bulk-deactivate');

/*
|--------------------------------------------------------------------------
| Product Actions
|--------------------------------------------------------------------------
*/

Route::post(
    '/products/{product}/duplicate',
    [ProductController::class, 'duplicate']
)->name('products.duplicate');

Route::post(
    '/products/{product}/toggle-featured',
    [ProductController::class, 'toggleFeatured']
)->name('products.toggle-featured');

Route::post(
    '/products/{product}/toggle-status',
    [ProductController::class, 'toggleStatus']
)->name('products.toggle-status');

/*
|--------------------------------------------------------------------------
| Edit / Update / Delete
|--------------------------------------------------------------------------
*/

Route::get(
    '/products/{product}/edit',
    [ProductController::class, 'edit']
)->name('products.edit');

Route::put(
    '/products/{product}',
    [ProductController::class, 'update']
)->name('products.update');

Route::delete(
    '/products/{product}',
    [ProductController::class, 'destroy']
)->name('products.destroy');