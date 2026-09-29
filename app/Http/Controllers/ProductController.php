<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProductController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Search Suggestions
    |--------------------------------------------------------------------------
    */

    public function suggestions(Request $request)
    {
        $q = $request->validate([
            'q' => 'nullable|string|max:255',
        ])['q'] ?? '';

        if (blank($q)) {
            return response()->json([]);
        }

        $suggestions = Product::where('name', 'like', "%{$q}%")
            ->limit(5)
            ->pluck('name');

        return response()->json($suggestions);
    }

    /*
    |--------------------------------------------------------------------------
    | Product Index
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',

            'category' => 'nullable|string|max:255',

            'min_price' => 'nullable|numeric|min:0',

            'max_price' => 'nullable|numeric|min:0',

            'date_from' => 'nullable|date',

            'date_to' => 'nullable|date',

            'stock_filter' => 'nullable|in:all,low,out',

            'status' => 'nullable|in:all,active,inactive',

            'featured' => 'nullable|in:all,featured,not_featured',

            'sort' => 'nullable|in:newest,oldest,name_asc,name_desc,price_low,price_high,stock_low,stock_high',

            'per_page' => 'nullable|integer|in:5,10,20,50',
        ]);

        $search = $validated['search'] ?? '';

        $category = $validated['category'] ?? '';

        $minPrice = $validated['min_price'] ?? '';

        $maxPrice = $validated['max_price'] ?? '';

        $dateFrom = $validated['date_from'] ?? '';

        $dateTo = $validated['date_to'] ?? '';

        $stockFilter = $validated['stock_filter'] ?? 'all';

        $status = $validated['status'] ?? 'all';

        $featured = $validated['featured'] ?? 'all';

        $sort = $validated['sort'] ?? 'newest';

        $perPage = (int) ($validated['per_page'] ?? 5);

        /*
        |--------------------------------------------------------------------------
        | Base Query
        |--------------------------------------------------------------------------
        */

        $query = Product::query();

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('detail', 'like', "%{$search}%")
                    ->orWhere('price', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Category
        |--------------------------------------------------------------------------
        */

        if ($category !== '') {
            $query->where('category', $category);
        }

        /*
        |--------------------------------------------------------------------------
        | Price
        |--------------------------------------------------------------------------
        */

        if ($minPrice !== '') {
            $query->where('price', '>=', $minPrice);
        }

        if ($maxPrice !== '') {
            $query->where('price', '<=', $maxPrice);
        }

        /*
        |--------------------------------------------------------------------------
        | Date
        |--------------------------------------------------------------------------
        */

        if ($dateFrom !== '') {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        /*
        |--------------------------------------------------------------------------
        | Stock Filter
        |--------------------------------------------------------------------------
        */

        if ($stockFilter === 'low') {
            $query->where('stock', '>', 0)
                ->where('stock', '<=', 5);
        }

        if ($stockFilter === 'out') {
            $query->where('stock', '<=', 0);
        }

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        /*
        |--------------------------------------------------------------------------
        | Featured
        |--------------------------------------------------------------------------
        */

        if ($featured === 'featured') {
            $query->where('is_featured', true);
        }

        if ($featured === 'not_featured') {
            $query->where('is_featured', false);
        }

        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */

        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;

            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;

            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;

            case 'price_low':
                $query->orderBy('price', 'asc');
                break;

            case 'price_high':
                $query->orderBy('price', 'desc');
                break;

            case 'stock_low':
                $query->orderBy('stock', 'asc');
                break;

            case 'stock_high':
                $query->orderBy('stock', 'desc');
                break;

            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $products = $query
            ->paginate($perPage)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Filtered Statistics
        |--------------------------------------------------------------------------
        */

        $statisticsQuery = $this->buildFilterQuery(
            $search,
            $category,
            $minPrice,
            $maxPrice,
            $dateFrom,
            $dateTo,
            $stockFilter,
            $status,
            $featured
        );

        $filteredCount = (clone $statisticsQuery)->count();

        $averagePrice = (clone $statisticsQuery)->avg('price');

        $lowestPrice = (clone $statisticsQuery)->min('price');

        $highestPrice = (clone $statisticsQuery)->max('price');

        $inventoryValue = (clone $statisticsQuery)
            ->selectRaw('COALESCE(SUM(price * stock), 0) as total')
            ->value('total');

        /*
        |--------------------------------------------------------------------------
        | Categories
        |--------------------------------------------------------------------------
        */

        $categories = Product::whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        /*
        |--------------------------------------------------------------------------
        | Main Statistics
        |--------------------------------------------------------------------------
        */

        $totalProducts = Product::count();

        $lowStockProducts = Product::where('stock', '>', 0)
            ->where('stock', '<=', 5)
            ->count();

        $outOfStockProducts = Product::where('stock', '<=', 0)
            ->count();

        $activeProducts = Product::where('status', 'active')
            ->count();

        $inactiveProducts = Product::where('status', 'inactive')
            ->count();

        $featuredProducts = Product::where('is_featured', true)
            ->count();

        return Inertia::render('Product/Index', [
            'products' => $products,

            'filters' => [
                'search' => $search,
                'category' => $category,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'stock_filter' => $stockFilter,
                'status' => $status,
                'featured' => $featured,
                'sort' => $sort,
                'per_page' => $perPage,
            ],

            'statistics' => [
                'total_products' => $totalProducts,
                'filtered_products' => $filteredCount,

                'average_price' => round(
                    (float) ($averagePrice ?? 0),
                    2
                ),

                'lowest_price' => round(
                    (float) ($lowestPrice ?? 0),
                    2
                ),

                'highest_price' => round(
                    (float) ($highestPrice ?? 0),
                    2
                ),

                'inventory_value' => round(
                    (float) ($inventoryValue ?? 0),
                    2
                ),

                'low_stock_products' => $lowStockProducts,

                'out_of_stock_products' => $outOfStockProducts,

                'active_products' => $activeProducts,

                'inactive_products' => $inactiveProducts,

                'featured_products' => $featuredProducts,
            ],

            'categories' => $categories,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Reusable Filter Query
    |--------------------------------------------------------------------------
    */

    private function buildFilterQuery(
        $search,
        $category,
        $minPrice,
        $maxPrice,
        $dateFrom,
        $dateTo,
        $stockFilter,
        $status,
        $featured
    ) {
        $query = Product::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('detail', 'like', "%{$search}%")
                    ->orWhere('price', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($category !== '') {
            $query->where('category', $category);
        }

        if ($minPrice !== '') {
            $query->where('price', '>=', $minPrice);
        }

        if ($maxPrice !== '') {
            $query->where('price', '<=', $maxPrice);
        }

        if ($dateFrom !== '') {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($stockFilter === 'low') {
            $query->where('stock', '>', 0)
                ->where('stock', '<=', 5);
        }

        if ($stockFilter === 'out') {
            $query->where('stock', '<=', 0);
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($featured === 'featured') {
            $query->where('is_featured', true);
        }

        if ($featured === 'not_featured') {
            $query->where('is_featured', false);
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle Featured
    |--------------------------------------------------------------------------
    */

    public function toggleFeatured(Product $product)
    {
        $product->update([
            'is_featured' => ! $product->is_featured,
        ]);

        return back()->with(
            'success',
            'Featured status updated successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle Status
    |--------------------------------------------------------------------------
    */

    public function toggleStatus(Product $product)
    {
        $product->update([
            'status' => $product->status === 'active'
                ? 'inactive'
                : 'active',
        ]);

        return back()->with(
            'success',
            'Product status updated successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Delete
    |--------------------------------------------------------------------------
    */

    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:products,id',
        ]);

        Product::whereIn('id', $validated['ids'])->delete();

        return back()->with(
            'success',
            count($validated['ids']) . ' product(s) deleted successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Activate
    |--------------------------------------------------------------------------
    */

    public function bulkActivate(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:products,id',
        ]);

        Product::whereIn('id', $validated['ids'])
            ->update([
                'status' => 'active',
            ]);

        return back()->with(
            'success',
            count($validated['ids']) . ' product(s) activated.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Deactivate
    |--------------------------------------------------------------------------
    */

    public function bulkDeactivate(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:products,id',
        ]);

        Product::whereIn('id', $validated['ids'])
            ->update([
                'status' => 'inactive',
            ]);

        return back()->with(
            'success',
            count($validated['ids']) . ' product(s) deactivated.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Duplicate Product
    |--------------------------------------------------------------------------
    */

    public function duplicate(Product $product)
    {
        $copy = $product->replicate();

        $copy->name = $product->name . ' Copy';

        $copy->status = 'active';

        $copy->is_featured = false;

        $copy->save();

        return back()->with(
            'success',
            'Product duplicated successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CSV Export
    |--------------------------------------------------------------------------
    */

    public function exportCsv(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'stock_filter' => 'nullable|in:all,low,out',
            'status' => 'nullable|in:all,active,inactive',
            'featured' => 'nullable|in:all,featured,not_featured',
            'sort' => 'nullable|in:newest,oldest,name_asc,name_desc,price_low,price_high,stock_low,stock_high',
        ]);

        $search = $validated['search'] ?? '';

        $category = $validated['category'] ?? '';

        $minPrice = $validated['min_price'] ?? '';

        $maxPrice = $validated['max_price'] ?? '';

        $dateFrom = $validated['date_from'] ?? '';

        $dateTo = $validated['date_to'] ?? '';

        $stockFilter = $validated['stock_filter'] ?? 'all';

        $status = $validated['status'] ?? 'all';

        $featured = $validated['featured'] ?? 'all';

        $sort = $validated['sort'] ?? 'newest';

        $query = $this->buildFilterQuery(
            $search,
            $category,
            $minPrice,
            $maxPrice,
            $dateFrom,
            $dateTo,
            $stockFilter,
            $status,
            $featured
        );

        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;

            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;

            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;

            case 'price_low':
                $query->orderBy('price', 'asc');
                break;

            case 'price_high':
                $query->orderBy('price', 'desc');
                break;

            case 'stock_low':
                $query->orderBy('stock', 'asc');
                break;

            case 'stock_high':
                $query->orderBy('stock', 'desc');
                break;

            default:
                $query->orderBy('created_at', 'asc');
                break;
        }

        $fileName = 'products-export-' .
            now()->format('Y-m-d-H-i-s') .
            '.csv';

        return response()->streamDownload(
            function () use ($query) {

                $handle = fopen(
                    'php://output',
                    'w'
                );

                fwrite(
                    $handle,
                    "\xEF\xBB\xBF"
                );

                fputcsv($handle, [
                    'ID',
                    'Product Name',
                    'Category',
                    'Detail',
                    'Price',
                    'Stock',
                    'Status',
                    'Featured',
                    'Created At',
                    'Updated At',
                ]);

                $query->chunk(
                    500,
                    function ($products) use ($handle) {

                        foreach ($products as $product) {

                            fputcsv($handle, [
                                $product->id,
                                $product->name,
                                $product->category ?? '',
                                $product->detail ?? '',
                                $product->price ?? '',
                                $product->stock,
                                $product->status,
                                $product->is_featured
                                    ? 'Yes'
                                    : 'No',
                                $product->created_at?->format(
                                    'Y-m-d H:i:s'
                                ),
                                $product->updated_at?->format(
                                    'Y-m-d H:i:s'
                                ),
                            ]);
                        }
                    }
                );

                fclose($handle);
            },
            $fileName,
            [
                'Content-Type' =>
                    'text/csv; charset=UTF-8',
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Statistics
    |--------------------------------------------------------------------------
    */

    public function statistics(Request $request)
    {
        $totalProducts = Product::count();

        $averagePrice = Product::avg('price');

        $lowestPrice = Product::min('price');

        $highestPrice = Product::max('price');

        $inventoryValue = Product::selectRaw(
            'COALESCE(SUM(price * stock), 0) as total'
        )->value('total');

        $todayProducts = Product::whereDate(
            'created_at',
            today()
        )->count();

        $thisMonthProducts = Product::whereMonth(
            'created_at',
            now()->month
        )
            ->whereYear(
                'created_at',
                now()->year
            )
            ->count();

        $lowStockProducts = Product::where('stock', '>', 0)
            ->where('stock', '<=', 5)
            ->count();

        $outOfStockProducts = Product::where('stock', '<=', 0)
            ->count();

        $activeProducts = Product::where(
            'status',
            'active'
        )->count();

        $inactiveProducts = Product::where(
            'status',
            'inactive'
        )->count();

        $featuredProducts = Product::where(
            'is_featured',
            true
        )->count();

        $recentProducts = Product::oldest()
            ->limit(7)
            ->get();

        return Inertia::render(
            'Product/Statistics',
            [
                'statistics' => [
                    'total_products' => $totalProducts,

                    'average_price' => round(
                        (float) ($averagePrice ?? 0),
                        2
                    ),

                    'lowest_price' => round(
                        (float) ($lowestPrice ?? 0),
                        2
                    ),

                    'highest_price' => round(
                        (float) ($highestPrice ?? 0),
                        2
                    ),

                    'inventory_value' => round(
                        (float) ($inventoryValue ?? 0),
                        2
                    ),

                    'today_products' => $todayProducts,

                    'this_month_products' =>
                        $thisMonthProducts,

                    'low_stock_products' =>
                        $lowStockProducts,

                    'out_of_stock_products' =>
                        $outOfStockProducts,

                    'active_products' =>
                        $activeProducts,

                    'inactive_products' =>
                        $inactiveProducts,

                    'featured_products' =>
                        $featuredProducts,
                ],

                'recentProducts' =>
                    $recentProducts,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        return Inertia::render(
            'Product/Create'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',

            'category' =>
                'nullable|string|max:255',

            'detail' =>
                'nullable|string',

            'price' =>
                'nullable|numeric|min:0',

            'stock' =>
                'required|integer|min:0',

            'status' =>
                'required|in:active,inactive',

            'is_featured' =>
                'boolean',
        ]);

        Product::create([
            'name' =>
                $validated['name'],

            'category' =>
                $validated['category'] ?? null,

            'detail' =>
                $validated['detail'] ?? null,

            'price' =>
                $validated['price'] ?? null,

            'stock' =>
                $validated['stock'],

            'status' =>
                $validated['status'],

            'is_featured' =>
                $validated['is_featured'] ?? false,
        ]);

        return redirect()
            ->route('products.index')
            ->with(
                'success',
                'Product created successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(Product $product)
    {
        return Inertia::render(
            'Product/Edit',
            [
                'product' => $product,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        Product $product
    ) {
        $validated = $request->validate([
            'name' =>
                'required|string|max:255',

            'category' =>
                'nullable|string|max:255',

            'detail' =>
                'nullable|string',

            'price' =>
                'nullable|numeric|min:0',

            'stock' =>
                'required|integer|min:0',

            'status' =>
                'required|in:active,inactive',

            'is_featured' =>
                'boolean',
        ]);

        $product->update([
            'name' =>
                $validated['name'],

            'category' =>
                $validated['category'] ?? null,

            'detail' =>
                $validated['detail'] ?? null,

            'price' =>
                $validated['price'] ?? null,

            'stock' =>
                $validated['stock'],

            'status' =>
                $validated['status'],

            'is_featured' =>
                $validated['is_featured'] ?? false,
        ]);

        return redirect()
            ->route('products.index')
            ->with(
                'success',
                'Product updated successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()
            ->route('products.index')
            ->with(
                'success',
                'Product deleted successfully.'
            );
    }
}