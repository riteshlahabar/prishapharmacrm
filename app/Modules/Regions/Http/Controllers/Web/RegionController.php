<?php

namespace App\Modules\Regions\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Regions\Exceptions\RegionInUse;
use App\Modules\Regions\Http\Requests\StoreRegionRequest;
use App\Modules\Regions\Services\Contracts\RegionServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin CRUD for territories. Validates and delegates; the rules are in
 * RegionService and the queries in EloquentRegionRepository.
 */
class RegionController extends Controller
{
    public function __construct(private readonly RegionServiceInterface $regions) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'status' => $request->string('status')->value(),
        ];

        $regions = $this->regions->paginate($filters, 20);

        return view('regions::index', [
            'regions' => $regions,
            'filters' => $filters,
            // Counted in one pass here so the table does not query per row.
            'usage' => $this->regions->usageCounts($regions->pluck('id')->all()),
        ]);
    }

    public function create(): View
    {
        return view('regions::form', ['region' => null]);
    }

    public function store(StoreRegionRequest $request): RedirectResponse
    {
        $region = $this->regions->create($request->payload());

        return redirect()->route('regions.index')
            ->with('success', "Region {$region->name} created.");
    }

    public function show(int|string $region): View
    {
        $model = $this->regions->find($region);

        return view('regions::show', [
            'region' => $model,
            'usage' => $this->regions->usageCounts([$model->id])[$model->id] ?? [],
        ]);
    }

    public function edit(int|string $region): View
    {
        return view('regions::form', ['region' => $this->regions->find($region)]);
    }

    public function update(StoreRegionRequest $request, int|string $region): RedirectResponse
    {
        $updated = $this->regions->update($region, $request->payload());

        return redirect()->route('regions.index')
            ->with('success', "Region {$updated->name} updated.");
    }

    public function destroy(int|string $region): RedirectResponse
    {
        try {
            $this->regions->delete($region);
        } catch (RegionInUse $e) {
            return redirect()->route('regions.index')->with('error', $e->getMessage());
        }

        return redirect()->route('regions.index')->with('success', 'Region deleted.');
    }

    public function toggle(int|string $region): RedirectResponse
    {
        $active = $this->regions->toggleActive($region);

        return redirect()->route('regions.index')
            ->with('success', $active ? 'Region activated.' : 'Region deactivated.');
    }
}
