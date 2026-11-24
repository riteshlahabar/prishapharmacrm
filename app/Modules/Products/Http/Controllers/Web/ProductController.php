<?php

namespace App\Modules\Products\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Products\Exceptions\ProductInUse;
use App\Modules\Products\Http\Requests\StoreProductRequest;
use App\Modules\Products\Services\Contracts\ProductServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin CRUD for the product master. Validates and delegates — the rules are
 * in ProductService and the queries in EloquentProductRepository.
 */
class ProductController extends Controller
{
    public function __construct(private readonly ProductServiceInterface $products) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'status' => $request->string('status')->value(),
            'in_stock' => $request->boolean('in_stock'),
        ];

        return view('products::index', [
            'products' => $this->products->paginate($filters, 20),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('products::form', ['product' => null]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->products->create($request->payload());

        return redirect()->route('products.index')
            ->with('success', "{$product->name} added.");
    }

    public function edit(int|string $product): View
    {
        return view('products::form', ['product' => $this->products->find($product)]);
    }

    public function update(StoreProductRequest $request, int|string $product): RedirectResponse
    {
        $updated = $this->products->update($product, $request->payload());

        return redirect()->route('products.index')
            ->with('success', "{$updated->name} updated.");
    }

    public function destroy(int|string $product): RedirectResponse
    {
        try {
            $this->products->delete($product);
        } catch (ProductInUse $e) {
            return redirect()->route('products.index')->with('error', $e->getMessage());
        }

        return redirect()->route('products.index')->with('success', 'Product deleted.');
    }

    public function toggle(int|string $product): RedirectResponse
    {
        $active = $this->products->toggleActive($product);

        return redirect()->route('products.index')->with(
            'success',
            $active
                ? 'Product activated.'
                : 'Product deactivated — it drops out of both apps, past records keep it.',
        );
    }
}
