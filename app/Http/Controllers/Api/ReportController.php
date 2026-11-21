<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;

class ReportController extends Controller
{
    public function salesReport()
    {
        $salesByProduct = Order::where('status', 'confirmed')
            ->selectRaw('product_id, sum(quantity) as total_quantity, sum(total_amount) as total_amount')
            ->groupBy('product_id')
            ->with('product')
            ->get();

        $salesByMonth = Order::where('status', 'confirmed')
            ->selectRaw('DATE_FORMAT(order_date, "%Y-%m") as month, sum(total_amount) as total, count(*) as count')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'by_product' => $salesByProduct,
                'by_month' => $salesByMonth,
            ],
        ]);
    }

    public function inventoryReport()
    {
        $lowStock = Product::where('stock_quantity', '<=', 50)
            ->orderBy('stock_quantity', 'asc')
            ->get();

        $categorySummary = Product::selectRaw('category, count(*) as product_count, sum(stock_quantity) as total_stock')
            ->groupBy('category')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'low_stock' => $lowStock,
                'by_category' => $categorySummary,
            ],
        ]);
    }
}
