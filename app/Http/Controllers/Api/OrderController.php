<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderRequest;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['customer', 'product'])
            ->orderBy('order_date', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $orders->items(),
            'total' => $orders->total(),
            'per_page' => $orders->perPage(),
            'current_page' => $orders->currentPage(),
        ]);
    }

    public function show($id)
    {
        $order = Order::with(['customer', 'product'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    public function store(OrderRequest $request)
    {
        $data = $request->validated();
        $data['order_number'] = 'ORD' . time() . str_pad($request->user()->id, 3, '0', STR_PAD_LEFT);
        $data['mr_id'] = $request->user()->id;

        $order = Order::create($data);

        if ($order->status === 'confirmed') {
            $product = $order->product;
            $product->stock_quantity -= $order->quantity;
            $product->save();
        }

        return response()->json([
            'success' => true,
            'data' => $order->load(['customer', 'product']),
            'message' => 'Order created successfully',
        ], 201);
    }

    public function update(OrderRequest $request, $id)
    {
        $order = Order::findOrFail($id);
        $data = $request->validated();

        if (isset($data['status']) && $order->status !== $data['status']) {
            $product = $order->product;
            if ($data['status'] === 'confirmed' && $order->status !== 'confirmed') {
                $product->stock_quantity -= $order->quantity;
            } elseif ($order->status === 'confirmed' && $data['status'] !== 'confirmed') {
                $product->stock_quantity += $order->quantity;
            }
            $product->save();
        }

        $order->update($data);

        return response()->json([
            'success' => true,
            'data' => $order->load(['customer', 'product']),
            'message' => 'Order updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully',
        ]);
    }
}
