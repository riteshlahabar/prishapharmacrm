<?php

namespace App\Modules\Users\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Regions\Services\Contracts\RegionAssignmentServiceInterface;
use App\Modules\Regions\Services\Contracts\RegionServiceInterface;
use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Http\Requests\StoreUserRequest;
use App\Modules\Users\Http\Requests\UpdateUserRequest;
use App\Modules\Users\Services\Contracts\UserServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * MR and telecaller CRUD are the same screens over the same table, differing
 * only in role, route names and wording — so the behaviour lives here once and
 * each subclass declares those three things (OCP: a third kind of user is a new
 * subclass, not an edit here).
 *
 * The controller validates and delegates; every rule is in UserService and
 * every query in EloquentUserRepository.
 */
abstract class UserCrudController extends Controller
{
    public function __construct(
        protected readonly UserServiceInterface $users,
        protected readonly RegionServiceInterface $regions,
        protected readonly RegionAssignmentServiceInterface $assignment,
    ) {}

    /** Which role this controller administers. */
    abstract protected function role(): UserRole;

    /** Resource route name, e.g. `mrs` — used for every redirect and link. */
    abstract protected function routeName(): string;

    /** ['singular' => 'Medical Rep', 'plural' => 'Medical Reps'] */
    abstract protected function titles(): array;

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'status' => $request->string('status')->value(),
        ];

        return view('users::index', $this->viewData([
            'users' => $this->users->paginateForRole($this->role(), $filters, 20),
            'filters' => $filters,
        ]));
    }

    public function create(): View
    {
        return view('users::form', $this->viewData([
            'user' => null,
            'regionOptions' => $this->regions->activeOptions(),
            'selectedRegions' => [],
        ]));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = $this->users->createInRole($this->role(), $request->payload());

        return redirect()
            ->route("{$this->routeName()}.index")
            ->with('success', "{$this->titles()['singular']} {$user->name} created.");
    }

    public function show(int|string $user): View
    {
        $model = $this->users->findForRole($this->role(), $user);

        return view('users::show', $this->viewData([
            'user' => $model,
            'regions' => $this->assignment->regionsFor($model),
        ]));
    }

    public function edit(int|string $user): View
    {
        $model = $this->users->findForRole($this->role(), $user);

        return view('users::form', $this->viewData([
            'user' => $model,
            'regionOptions' => $this->regions->activeOptions(),
            'selectedRegions' => $this->assignment->idsFor($model),
        ]));
    }

    public function update(UpdateUserRequest $request, int|string $user): RedirectResponse
    {
        $updated = $this->users->updateInRole($this->role(), $user, $request->payload());

        return redirect()
            ->route("{$this->routeName()}.index")
            ->with('success', "{$this->titles()['singular']} {$updated->name} updated.");
    }

    public function destroy(int|string $user): RedirectResponse
    {
        $this->users->delete($this->role(), $user);

        return redirect()
            ->route("{$this->routeName()}.index")
            ->with('success', "{$this->titles()['singular']} deleted.");
    }

    /** Enable / disable without opening the edit form. */
    public function toggle(int|string $user): RedirectResponse
    {
        $active = $this->users->toggleActive($this->role(), $user);

        return redirect()
            ->route("{$this->routeName()}.index")
            ->with('success', $active ? 'Account enabled.' : 'Account disabled.');
    }

    /** Context every shared view needs to render links and wording. */
    protected function viewData(array $data): array
    {
        return $data + [
            'role' => $this->role(),
            'routeName' => $this->routeName(),
            'titles' => $this->titles(),
            // An MR travels one territory, a telecaller works several.
            'multipleRegions' => $this->role() === UserRole::Telecaller,
        ];
    }
}
