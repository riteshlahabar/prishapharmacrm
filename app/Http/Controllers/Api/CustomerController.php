<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Models\Customer;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::orderBy('customer_name')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $customers->items(),
            'total' => $customers->total(),
            'per_page' => $customers->perPage(),
            'current_page' => $customers->currentPage(),
        ]);
    }

    public function search($request)
    {
        $query = $request->get('q', '');

        $customers = Customer::where('customer_name', 'like', "%{$query}%")
            ->orWhere('customer_email', 'like', "%{$query}%")
            ->orWhere('customer_phone', 'like', "%{$query}%")
            ->orWhere('customer_city', 'like', "%{$query}%")
            ->orderBy('customer_name')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $customers->items(),
            'total' => $customers->total(),
            'per_page' => $customers->perPage(),
            'current_page' => $customers->currentPage(),
        ]);
    }

    public function show($id)
    {
        $customer = Customer::with('orders')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $customer,
        ]);
    }

    public function store(CustomerRequest $request)
    {
        $customer = Customer::create($request->validated());

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Customer created successfully',
        ], 201);
    }

    public function update(CustomerRequest $request, $id)
    {
        $customer = Customer::findOrFail($id);
        $customer->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Customer updated successfully',
        ]);
    }
}
