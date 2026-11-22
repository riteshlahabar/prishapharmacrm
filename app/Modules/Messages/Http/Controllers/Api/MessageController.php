<?php

namespace App\Modules\Messages\Http\Controllers\Api;

use App\Core\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Messages\Http\Requests\SendMessageRequest;
use App\Modules\Messages\Http\Resources\MessageResource;
use App\Modules\Messages\Http\Resources\RecipientResource;
use App\Modules\Messages\Services\Contracts\MessageServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * `/api/v1/messages` — in-app messaging, **no Firebase**.
 *
 * The MR app polls `unread-count` on resume and on a timer, then loads the
 * inbox when it changes. That endpoint is deliberately one indexed COUNT and
 * nothing else (docs/api/v1-mr.md).
 *
 * Sending is the telecaller's half and is gated on their ability; reading is
 * the MR's. Both are scoped by the service to the signed-in user.
 */
class MessageController extends Controller
{
    public function __construct(private readonly MessageServiceInterface $messages) {}

    /** MR inbox. `?unread=1` narrows it to what has not been read. */
    public function index(Request $request): JsonResponse
    {
        $page = $this->messages->inboxFor($request->user(), [
            'read' => $request->boolean('unread') ? 'unread' : null,
            'person_id' => $request->input('person_id'),
        ], (int) $request->integer('per_page', 20));

        return ApiResponse::paginated(
            $page->through(fn ($message) => (new MessageResource(
                $message->loadMissing(['sender', 'person'])
            ))->resolve()),
            'Inbox loaded.',
        );
    }

    /** The polled endpoint. Kept to a count so it stays cheap on a timer. */
    public function unreadCount(Request $request): JsonResponse
    {
        return ApiResponse::success(
            ['unread' => $this->messages->unreadCountFor($request->user())],
            'Unread count loaded.',
        );
    }

    public function markRead(Request $request, int|string $message): JsonResponse
    {
        $read = $this->messages->markRead($request->user(), $message);

        return ApiResponse::success(
            new MessageResource($read->load(['sender', 'person'])),
            'Message marked read.',
        );
    }

    public function markAllRead(Request $request): JsonResponse
    {
        return ApiResponse::success(
            ['marked' => $this->messages->markAllRead($request->user())],
            'Inbox marked read.',
        );
    }

    /** Telecaller side: send to an MR. */
    public function store(SendMessageRequest $request): JsonResponse
    {
        $message = $this->messages->sendAs($request->user(), $request->payload());

        return ApiResponse::success(
            new MessageResource($message->load(['recipient', 'person'])),
            'Message sent.',
            201,
        );
    }

    /** Telecaller side: who they may write to. */
    public function recipients(Request $request): JsonResponse
    {
        $recipients = $this->messages->recipientsFor($request->user(), $request->input('person_id'));

        return ApiResponse::success(
            RecipientResource::collection($recipients->load('region'))->resolve(),
            'Recipients loaded.',
        );
    }

    /** Telecaller side: what they have sent. */
    public function sent(Request $request): JsonResponse
    {
        $page = $this->messages->paginate([
            'from_user_id' => $request->user()->id,
            'person_id' => $request->input('person_id'),
        ], (int) $request->integer('per_page', 20));

        return ApiResponse::paginated(
            $page->through(fn ($message) => (new MessageResource(
                $message->loadMissing(['recipient', 'person'])
            ))->resolve()),
            'Sent messages loaded.',
        );
    }
}
