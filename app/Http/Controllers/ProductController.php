<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Search suggestions
     */
    public function suggestions(Request $request)
    {
        $q = $request->validate([
            'q' => 'nullable|string|max:255'
        ])['q'];

        if (blank($q)) {
            return response()->json([]);
        }

        $suggestions = Product::where('name', 'like', "%{$q}%")
            ->limit(5)
            ->pluck('name');

        return response()->json($suggestions);
    }

    /**
     * Product list with:
     * - Live search
     * - Advanced price filtering
     * - Date filtering
     * - Dynamic sorting
     * - Dynamic page size
     * - Pagination
     * - Statistics
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'sort' => 'nullable|in:newest,oldest,name_asc,name_desc,price_low,price_high',
            'per_page' => 'nullable|integer|in:5,10,20,50',
        ]);

        $search = $validated['search'] ?? '';
        $minPrice = $validated['min_price'] ?? '';
        $maxPrice = $validated['max_price'] ?? '';
        $dateFrom = $validated['date_from'] ?? '';
        $dateTo = $validated['date_to'] ?? '';
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
        | Live Search
        |--------------------------------------------------------------------------
        */

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('detail', 'like', "%{$search}%")
                    ->orWhere('price', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Minimum Price
        |--------------------------------------------------------------------------
        */

        if ($minPrice !== '') {
            $query->where('price', '>=', $minPrice);
        }

        /*
        |--------------------------------------------------------------------------
        | Maximum Price
        |--------------------------------------------------------------------------
        */

        if ($maxPrice !== '') {
            $query->where('price', '<=', $maxPrice);
        }

        /*
        |--------------------------------------------------------------------------
        | Date From
        |--------------------------------------------------------------------------
        */

        if ($dateFrom !== '') {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        /*
        |--------------------------------------------------------------------------
        | Date To
        |--------------------------------------------------------------------------
        */

        if ($dateTo !== '') {
            $query->whereDate('created_at', '<=', $dateTo);
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
        | Statistics for Current Filters
        |--------------------------------------------------------------------------
        */

        $statisticsQuery = Product::query();

        if ($search !== '') {
            $statisticsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('detail', 'like', "%{$search}%")
                    ->orWhere('price', 'like', "%{$search}%");
            });
        }

        if ($minPrice !== '') {
            $statisticsQuery->where('price', '>=', $minPrice);
        }

        if ($maxPrice !== '') {
            $statisticsQuery->where('price', '<=', $maxPrice);
        }

        if ($dateFrom !== '') {
            $statisticsQuery->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $statisticsQuery->whereDate('created_at', '<=', $dateTo);
        }

        $filteredCount = (clone $statisticsQuery)->count();

        $averagePrice = (clone $statisticsQuery)->avg('price');
        $lowestPrice = (clone $statisticsQuery)->min('price');
        $highestPrice = (clone $statisticsQuery)->max('price');

        /*
        |--------------------------------------------------------------------------
        | Return Inertia Page
        |--------------------------------------------------------------------------
        */

        return Inertia::render('Product/Index', [
            'products' => $products,

            'filters' => [
                'search' => $search,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'sort' => $sort,
                'per_page' => $perPage,
            ],

            'statistics' => [
                'total_products' => Product::count(),
                'filtered_products' => $filteredCount,
                'average_price' => round((float) ($averagePrice ?? 0), 2),
                'lowest_price' => round((float) ($lowestPrice ?? 0), 2),
                'highest_price' => round((float) ($highestPrice ?? 0), 2),
            ],
        ]);
    }

    /**
     * Export filtered products to CSV.
     *
     * Exports products using the current:
     * - Search
     * - Minimum price
     * - Maximum price
     * - Date from
     * - Date to
     * - Sorting
     */
    public function exportCsv(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'sort' => 'nullable|in:newest,oldest,name_asc,name_desc,price_low,price_high',
        ]);

        $search = $validated['search'] ?? '';
        $minPrice = $validated['min_price'] ?? '';
        $maxPrice = $validated['max_price'] ?? '';
        $dateFrom = $validated['date_from'] ?? '';
        $dateTo = $validated['date_to'] ?? '';
        $sort = $validated['sort'] ?? 'newest';

        /*
        |--------------------------------------------------------------------------
        | Build Export Query
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
                    ->orWhere('price', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Price Filters
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
        | Date Filters
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

            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        /*
        |--------------------------------------------------------------------------
        | CSV File Name
        |--------------------------------------------------------------------------
        */

        $fileName = 'products-export-' .
            now()->format('Y-m-d-H-i-s') .
            '.csv';

        /*
        |--------------------------------------------------------------------------
        | Stream CSV Download
        |--------------------------------------------------------------------------
        */

        return response()->streamDownload(
            function () use ($query) {

                $handle = fopen('php://output', 'w');

                /*
                |--------------------------------------------------------------
                | UTF-8 BOM
                |--------------------------------------------------------------
                | Helps Microsoft Excel correctly detect UTF-8 CSV files.
                */

                fwrite($handle, "\xEF\xBB\xBF");

                /*
                |--------------------------------------------------------------
                | CSV Header
                |--------------------------------------------------------------
                */

                fputcsv($handle, [
                    'ID',
                    'Product Name',
                    'Product Detail',
                    'Price',
                    'Created At',
                    'Updated At',
                ]);

                /*
                |--------------------------------------------------------------
                | Export Products
                |--------------------------------------------------------------
                */

                $query->chunk(500, function ($products) use ($handle) {

                    foreach ($products as $product) {

                        fputcsv($handle, [
                            $product->id,
                            $product->name,
                            $product->detail ?? '',
                            $product->price ?? '',
                            $product->created_at?->format('Y-m-d H:i:s'),
                            $product->updated_at?->format('Y-m-d H:i:s'),
                        ]);
                    }
                });

                fclose($handle);
            },
            $fileName,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }

    /**
     * Product statistics dashboard
     */
    public function statistics(Request $request)
    {
        $totalProducts = Product::count();

        $averagePrice = Product::avg('price');
        $lowestPrice = Product::min('price');
        $highestPrice = Product::max('price');

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

        $recentProducts = Product::latest()
            ->limit(5)
            ->get();

        return Inertia::render('Product/Statistics', [
            'statistics' => [
                'total_products' => $totalProducts,
                'average_price' => round((float) ($averagePrice ?? 0), 2),
                'lowest_price' => round((float) ($lowestPrice ?? 0), 2),
                'highest_price' => round((float) ($highestPrice ?? 0), 2),
                'today_products' => $todayProducts,
                'this_month_products' => $thisMonthProducts,
            ],

            'recentProducts' => $recentProducts,
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
            'name' => 'required|string|max:255',
            'detail' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
        ]);

        Product::create(
            $request->only(
                'name',
                'detail',
                'price'
            )
        );

        return redirect()
            ->route('products.index')
            ->with(
                'success',
                'Product created successfully'
            );
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
    public function update(
        Request $request,
        Product $product
    ) {
        $request->validate([
            'name' => 'required|string|max:255',
            'detail' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
        ]);

        $product->update(
            $request->only(
                'name',
                'detail',
                'price'
            )
        );

        return redirect()
            ->route('products.index')
            ->with(
                'success',
                'Product updated successfully'
            );
    }

    /**
     * Delete product
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()
            ->route('products.index')
            ->with(
                'success',
                'Product deleted successfully'
            );
    }
}