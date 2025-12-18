<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Product list + live search + pagination
     */
    public function index(Request $request)
    {
        $search = $request->search;

        $products = Product::where(function ($q) use ($search) {
                if ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('detail', 'like', "%{$search}%") 
                    ->orWhere('price', 'like', "%{$search}%");
                }
            })
            ->latest()
            ->paginate(5) // pagination = 5
            ->withQueryString();

        return Inertia::render('Product/Index', [
            'products' => $products,
            'filters' => [
                'search' => $search
            ]
        ]);
    }



    /**
     * Create form
     */
    public function create()
    {
        return Inertia::render('Product/Create');
    }

    /**
     * Store product
     */
    
    public function store(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'detail' => 'nullable|string',
            'price'  => 'nullable|numeric',
        ]);

        Product::create($request->only('name', 'detail', 'price'));

        return redirect()->route('products.index')
            ->with('success', 'Product created successfully');
    }

    /**
     * Edit form
     */
    public function edit(Product $product)
    {
        return Inertia::render('Product/Edit', [
            'product' => $product
        ]);
    }

    /**
     * Update product
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'detail' => 'nullable|string',
            'price'  => 'nullable|numeric',
        ]);

        $product->update($request->only('name', 'detail', 'price'));

        return redirect()->route('products.index')
            ->with('success', 'Product updated successfully');
    }

    /**
     * Delete product
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully');
    }
}
