<?php

use App\Modules\Calls\Http\Controllers\Api\CallLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Calls — telecaller app endpoints (/api/v1 prefix from ModuleServiceProvider)
|--------------------------------------------------------------------------
| `ability:role:telecaller` refuses a token minted for the MR app, even though
| it authenticates fine. Calling is telecaller work.
|
| `follow-ups` is declared before `{callLog}` so the literal segment is not
| swallowed by the parameter.
*/

Route::middleware(['auth:sanctum', 'ability:role:telecaller'])
    ->prefix('call-logs')
    ->name('api.v1.call-logs.')
    ->group(function () {
        Route::get('follow-ups', [CallLogController::class, 'followUps'])->name('follow-ups');
        Route::get('/', [CallLogController::class, 'index'])->name('index');
        Route::post('/', [CallLogController::class, 'store'])->name('store');
        Route::patch('{callLog}', [CallLogController::class, 'update'])->name('update');
        Route::post('{callLog}/follow-up-done', [CallLogController::class, 'completeFollowUp'])
            ->name('follow-up-done');
    });
