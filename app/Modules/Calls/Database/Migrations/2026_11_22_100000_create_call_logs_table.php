<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One call a telecaller made on a person (docs/modules/calls.md).
 *
 * Unlike `attendances` there is no uniqueness here: calling the same person
 * twice in a day is normal work, and the history of attempts is the point.
 *
 * `called_at` is server-set on create, like the attendance stamps, so the
 * record of when work happened cannot be back-dated from the device.
 * `follow_up_at` is the opposite — a date the telecaller chooses, in the
 * future, which is why it is nullable and separately indexed: the follow-up
 * queue reads it on every app launch.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telecaller_id')->constrained('users')->cascadeOnDelete();
            // Losing a person must not erase the record of having called them.
            $table->foreignId('person_id')->constrained('persons')->restrictOnDelete();

            $table->timestamp('called_at');
            $table->string('status', 20)->index();
            $table->text('notes')->nullable();
            $table->timestamp('follow_up_at')->nullable();
            $table->timestamp('follow_up_done_at')->nullable();

            $table->timestamps();

            $table->index(['telecaller_id', 'called_at']);
            $table->index(['person_id', 'called_at']);
            // The follow-up queue: due call-backs for one telecaller, soonest
            // first. Ordering on the index keeps that query off a filesort.
            $table->index(['telecaller_id', 'follow_up_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};
