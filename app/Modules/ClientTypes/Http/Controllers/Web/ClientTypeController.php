<?php

namespace App\Modules\ClientTypes\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\ClientTypes\Exceptions\ClientTypeInUse;
use App\Modules\ClientTypes\Http\Requests\StoreClientTypeRequest;
use App\Modules\ClientTypes\Services\Contracts\ClientTypeServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin CRUD for the client-type master: the list that fills the Type field
 * on every client. Validates and delegates — the rules are in
 * ClientTypeService and the queries in EloquentClientTypeRepository.
 */
class ClientTypeController extends Controller
{
    public function __construct(private readonly ClientTypeServiceInterface $types) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'status' => $request->string('status')->value(),
        ];

        return view('clienttypes::index', [
            'types' => $this->types->paginate($filters, 20),
            'filters' => $filters,
            // One grouped query, so the table does not count per row.
            'usage' => $this->types->usageCounts(),
        ]);
    }

    public function create(): View
    {
        return view('clienttypes::form', ['type' => null]);
    }

    public function store(StoreClientTypeRequest $request): RedirectResponse
    {
        $type = $this->types->create($request->payload());

        return redirect()->route('client-types.index')
            ->with('success', "Client type {$type->label} created.");
    }

    public function edit(int|string $client_type): View
    {
        return view('clienttypes::form', ['type' => $this->types->find($client_type)]);
    }

    public function update(StoreClientTypeRequest $request, int|string $client_type): RedirectResponse
    {
        $updated = $this->types->update($client_type, $request->payload());

        return redirect()->route('client-types.index')
            ->with('success', "Client type {$updated->label} updated.");
    }

    public function destroy(int|string $client_type): RedirectResponse
    {
        try {
            $this->types->delete($client_type);
        } catch (ClientTypeInUse $e) {
            return redirect()->route('client-types.index')->with('error', $e->getMessage());
        }

        return redirect()->route('client-types.index')->with('success', 'Client type deleted.');
    }

    public function toggle(int|string $client_type): RedirectResponse
    {
        $active = $this->types->toggleActive($client_type);

        return redirect()->route('client-types.index')->with(
            'success',
            $active
                ? 'Client type activated.'
                : 'Client type deactivated — existing clients keep it, new ones cannot choose it.',
        );
    }
}
