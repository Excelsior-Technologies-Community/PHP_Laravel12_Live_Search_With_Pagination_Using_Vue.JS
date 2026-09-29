<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'detail',
        'price',
        'stock',
        'status',
        'is_featured',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'is_featured' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Original / Duplicate Relationship
    |--------------------------------------------------------------------------
    |
    | We use this field dynamically when duplicating products.
    |
    */

    protected $appends = [
        'stock_status',
    ];

    public function getStockStatusAttribute(): string
    {
        if ($this->stock <= 0) {
            return 'out_of_stock';
        }

        if ($this->stock <= 5) {
            return 'low_stock';
        }

        return 'in_stock';
    }
}