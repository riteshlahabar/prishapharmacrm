<?php

namespace App\Modules\Persons\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Persons\Enums\PersonType;
use App\Modules\Persons\Http\Requests\StorePersonRequest;
use App\Modules\Persons\Services\Contracts\PersonServiceInterface;
use App\Modules\Regions\Services\Contracts\RegionServiceInterface;
use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin CRUD for the contact book.
 *
 * The list goes through paginateForUser, so a telecaller with panel access
 * sees only persons in the regions they cover — the scope is enforced in the
 * service, not assembled here.
 */
class PersonController extends Controller
{
    public function __construct(
        private readonly PersonServiceInterface $persons,
        private readonly RegionServiceInterface $regions,
        private readonly UserRepositoryInterface $users,
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
            'persons' => $this->persons->paginateForUser($request->user(), $filters, 20),
            'filters' => $filters,
        ] + $this->pickers());
    }

    public function create(): View
    {
        return view('persons::form', ['person' => null] + $this->pickers());
    }

    public function store(StorePersonRequest $request): RedirectResponse
    {
        $person = $this->persons->create($request->payload());

        return redirect()->route('persons.index')
            ->with('success', "{$person->name} added.");
    }

    public function show(int|string $person): View
    {
        return view('persons::show', ['person' => $this->persons->find($person)]);
    }

    public function edit(int|string $person): View
    {
        return view('persons::form', ['person' => $this->persons->find($person)] + $this->pickers());
    }

    public function update(StorePersonRequest $request, int|string $person): RedirectResponse
    {
        $updated = $this->persons->update($person, $request->payload());

        return redirect()->route('persons.index')
            ->with('success', "{$updated->name} updated.");
    }

    public function destroy(int|string $person): RedirectResponse
    {
        $this->persons->delete($person);

        return redirect()->route('persons.index')->with('success', 'Person deleted.');
    }

    public function toggle(int|string $person): RedirectResponse
    {
        $active = $this->persons->toggleActive($person);

        return redirect()->route('persons.index')
            ->with('success', $active ? 'Person activated.' : 'Person deactivated.');
    }

    /** Dropdown data shared by the list filters and the form. */
    private function pickers(): array
    {
        return [
            'types' => PersonType::cases(),
            'regionOptions' => $this->regions->activeOptions(),
            'mrOptions' => $this->users->activeOfRole(UserRole::Mr),
            'telecallerOptions' => $this->users->activeOfRole(UserRole::Telecaller),
        ];
    }
}
