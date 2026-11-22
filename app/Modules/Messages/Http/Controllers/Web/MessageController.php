<?php

namespace App\Modules\Messages\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Messages\Services\Contracts\MessageServiceInterface;
use App\Modules\Users\Enums\UserRole;
use App\Modules\Users\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Message log. Messages are written in the telecaller app and read in the MR
 * app, so the panel reads and (with the permission) deletes, but never sends.
 *
 * A message has two owners, so a non-admin sees everything they sent *or*
 * were sent — unlike calls, where only the caller has a claim.
 */
class MessageController extends Controller
{
    public function __construct(
        private readonly MessageServiceInterface $messages,
        private readonly UserRepositoryInterface $users,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'from_user_id' => $request->input('from_user_id'),
            'to_user_id' => $request->input('to_user_id'),
            'read' => $request->string('read')->value(),
            'search' => $request->string('search')->trim()->value(),
        ];

        return view('messages::index', [
            'messages' => $this->messages->paginateForUser($request->user(), $filters, 25),
            'summary' => $this->messages->summaryForUser($request->user(), $filters),
            'filters' => $filters,
            'telecallerOptions' => $this->users->activeOfRole(UserRole::Telecaller),
            'mrOptions' => $this->users->activeOfRole(UserRole::Mr),
        ]);
    }

    public function show(Request $request, int|string $message): View
    {
        return view('messages::show', [
            'message' => $this->messages->findForUser($request->user(), $message),
        ]);
    }

    /** Resolved through the scope first: you cannot delete what you cannot see. */
    public function destroy(Request $request, int|string $message): RedirectResponse
    {
        $this->messages->delete($this->messages->findForUser($request->user(), $message)->id);

        return redirect()->route('messages.index')->with('success', 'Message deleted.');
    }
}
