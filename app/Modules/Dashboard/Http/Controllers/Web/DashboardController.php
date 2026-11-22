<?php

namespace App\Modules\Dashboard\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Services\Contracts\DashboardServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * The panel's landing screen, for whoever is signed in.
 *
 * There is one action and one view: an admin and a telecaller see the same
 * page, with the cards each may see. Which those are is decided by the
 * modules' own policies inside the service, not here and not in the template
 * (docs/modules/dashboard.md).
 */
class DashboardController extends Controller
{
    public function __construct(private readonly DashboardServiceInterface $dashboard) {}

    public function index(Request $request): View
    {
        return view('dashboard::index', [
            'data' => $this->dashboard->forUser(
                $request->user(),
                $this->dashboard->period($request->query('days')),
            ),
        ]);
    }
}
