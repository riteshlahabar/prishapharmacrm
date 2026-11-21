<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalCustomers = Customer::count();
        $totalProducts = Product::count();
        $totalOrders = Order::count();
        $confirmedOrders = Order::where('status', 'confirmed')->count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $lowStockProducts = Product::where('stock_quantity', '<=', 50)->count();

        $monthlySales = Order::where('status', 'confirmed')
            ->selectRaw('DATE_FORMAT(order_date, "%Y-%m") as month, sum(total_amount) as total')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(6)
            ->get();

        $recentOrders = Order::with(['customer', 'product'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_customers' => $totalCustomers,
                'total_products' => $totalProducts,
                'total_orders' => $totalOrders,
                'confirmed_orders' => $confirmedOrders,
                'pending_orders' => $pendingOrders,
                'low_stock_products' => $lowStockProducts,
                'monthly_sales' => $monthlySales,
                'recent_orders' => $recentOrders,
            ],
        ]);
    }
}
