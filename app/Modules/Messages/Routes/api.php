<?php

use App\Modules\Messages\Http\Controllers\Api\MessageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Messages — both apps (/api/v1 prefix from ModuleServiceProvider)
|--------------------------------------------------------------------------
| Messaging is one-directional in Phase 5: a telecaller writes, an MR reads.
| So the two halves carry different abilities rather than one shared gate.
|
| Literal segments are declared before `{message}` so they are not swallowed
| by the parameter.
*/

Route::middleware('auth:sanctum')->prefix('messages')->name('api.v1.messages.')->group(function () {
    // MR: the inbox and the poll.
    Route::middleware('ability:role:mr')->group(function () {
        Route::get('unread-count', [MessageController::class, 'unreadCount'])->name('unread-count');
        Route::post('read-all', [MessageController::class, 'markAllRead'])->name('read-all');
        Route::get('/', [MessageController::class, 'index'])->name('index');
        Route::post('{message}/read', [MessageController::class, 'markRead'])->name('read');
    });

    // Telecaller: sending, and what they have sent.
    Route::middleware('ability:role:telecaller')->group(function () {
        Route::get('sent', [MessageController::class, 'sent'])->name('sent');
        Route::post('/', [MessageController::class, 'store'])->name('store');
    });
});

// The recipient picker sits outside the `messages` prefix: the telecaller app
// asks "which MRs?", which is a question about people, not about a message.
Route::middleware(['auth:sanctum', 'ability:role:telecaller'])
    ->get('mrs', [MessageController::class, 'recipients'])
    ->name('api.v1.mrs.index');
