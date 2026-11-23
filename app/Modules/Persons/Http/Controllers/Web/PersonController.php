<?php

namespace App\Modules\Persons\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\ClientTypes\Services\Contracts\ClientTypeServiceInterface;
use App\Modules\Persons\Http\Requests\StorePersonRequest;
use App\Modules\Persons\Services\Contracts\PersonServiceInterface;
use App\Modules\Regions\Services\Contracts\RegionServiceInterface;
use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin CRUD for the contact book — "clients" in the panel, persons in the
 * code (docs/modules/persons.md).
 *
 * The list goes through paginateForUser, so a telecaller with panel access
 * sees only clients in the regions they cover — the scope is enforced in the
 * service, not assembled here.
 */
class PersonController extends Controller
{
    public function __construct(
        private readonly PersonServiceInterface $persons,
        private readonly RegionServiceInterface $regions,
        private readonly UserRepositoryInterface $users,
        private readonly ClientTypeServiceInterface $types,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'type' => $request->string('type')->value(),
            'region_id' => $request->input('region_id'),
            'assigned_mr_id' => $request->input('assigned_mr_id'),
            'assigned_telecaller_id' => $request->input('assigned_telecaller_id'),
            'status' => $request->string('status')->value(),
            'unassigned' => $request->boolean('unassigned'),
        ];

        return view('persons::index', [
            'clients' => $this->persons->paginateForUser($request->user(), $filters, 20),
            'filters' => $filters,
        ] + $this->pickers());
    }

    public function create(): View
    {
        return view('persons::form', ['person' => null] + $this->pickers(''));
    }

    public function store(StorePersonRequest $request): RedirectResponse
    {
        $person = $this->persons->create($request->payload());

        return redirect()->route('clients.index')
            ->with('success', "Client {$person->name} added.");
    }

    public function show(Request $request, int|string $person): View
    {
        return view('persons::show', [
            'person' => $this->persons->findForUser($request->user(), $person),
        ]);
    }

    public function edit(Request $request, int|string $person): View
    {
        $model = $this->persons->findForUser($request->user(), $person);

        return view('persons::form', ['person' => $model] + $this->pickers($model->type));
    }

    public function update(StorePersonRequest $request, int|string $person): RedirectResponse
    {
        $updated = $this->persons->update($person, $request->payload());

        return redirect()->route('clients.index')
            ->with('success', "Client {$updated->name} updated.");
    }

    public function destroy(int|string $person): RedirectResponse
    {
        $this->persons->delete($person);

        return redirect()->route('clients.index')->with('success', 'Client deleted.');
    }

    public function toggle(int|string $person): RedirectResponse
    {
        $active = $this->persons->toggleActive($person);

        return redirect()->route('clients.index')
            ->with('success', $active ? 'Client activated.' : 'Client deactivated.');
    }

    /**
     * Dropdown data shared by the list filters and the form.
     *
     * The form is given `optionsFor` so that editing a client whose type was
     * since retired still shows that type, instead of silently re-typing them
     * on the next save.
     */
    private function pickers(?string $currentType = null): array
    {
        return [
            'types' => $currentType === null
                ? $this->types->activeOptions()
                : $this->types->optionsFor($currentType),
            'regionOptions' => $this->regions->activeOptions(),
            'mrOptions' => $this->users->activeOfRole(UserRole::Mr),
            'telecallerOptions' => $this->users->activeOfRole(UserRole::Telecaller),
        ];
    }
}
