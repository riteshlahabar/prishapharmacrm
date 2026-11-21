<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_name',
        'product_code',
        'generic_name',
        'category',
        'manufacturer',
        'mrp',
        'selling_price',
        'stock_quantity',
        'description',
    ];

    protected $casts = [
        'mrp' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'stock_quantity' => 'integer',
    ];
}
