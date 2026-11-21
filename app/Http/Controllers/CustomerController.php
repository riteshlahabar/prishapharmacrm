<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::latest()->paginate(10);
        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_name'     => 'required|string|max:255',
            'customer_email'    => 'required|email|max:255|unique:customers',
            'customer_phone'    => 'required|string|max:20',
            'customer_address'  => 'nullable|string',
            'customer_city'     => 'nullable|string|max:255',
            'customer_state'    => 'nullable|string|max:255',
            'customer_zip'      => 'nullable|string|max:20',
            'customer_country'  => 'nullable|string|max:255',
            'customer_website'  => 'nullable|url|max:255',
        ]);

        Customer::create($request->all());

        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer)
    {
        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'customer_name'     => 'required|string|max:255',
            'customer_email'    => 'required|email|max:255|unique:customers,customer_email,' . $customer->id,
            'customer_phone'    => 'required|string|max:20',
            'customer_address'  => 'nullable|string',
            'customer_city'     => 'nullable|string|max:255',
            'customer_state'    => 'nullable|string|max:255',
            'customer_zip'      => 'nullable|string|max:20',
            'customer_country'  => 'nullable|string|max:255',
            'customer_website'  => 'nullable|url|max:255',
        ]);

        $customer->update($request->all());

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }
}
