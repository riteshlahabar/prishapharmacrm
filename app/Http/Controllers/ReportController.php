<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function salesReport(Request $request)
    {
        $start_date = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $end_date = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $orders = Order::with(['customer', 'product'])
            ->whereBetween('order_date', [$start_date, $end_date])
            ->orderBy('order_date', 'desc')
            ->get();

        $totalOrders = $orders->count();
        $totalRevenue = $orders->sum('total_price');
        $ordersByStatus = $orders->countBy('status');

        return view('reports.sales', compact('orders', 'totalOrders', 'totalRevenue', 'ordersByStatus', 'start_date', 'end_date'));
    }

    public function inventoryReport()
    {
        $products = Product::orderBy('product_name')->get();

        $totalProducts = $products->count();
        $totalStock = $products->sum('stock_quantity');
        $lowStockProducts = $products->filter(fn($p) => $p->stock_quantity <= 10);

        return view('reports.inventory', compact('products', 'totalProducts', 'totalStock', 'lowStockProducts'));
    }
}
