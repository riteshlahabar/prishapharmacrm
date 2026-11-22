<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A call an MR made on a person (docs/modules/visits.md).
 *
 * Two clocks are recorded on purpose. `in_time` / `out_time` are what the MR
 * states about the meeting, and `location_fetched_at` is when the device read
 * the GPS; `created_at` is when the server received it. A report can then show
 * a visit entered hours after the fact, or coordinates captured somewhere
 * other than where the form was filled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mr_id')->constrained('users')->cascadeOnDelete();
            // The visit outlives nothing: losing a person must not erase the
            // history of having called on them.
            $table->foreignId('person_id')->constrained('persons')->restrictOnDelete();

            $table->date('visit_date');
            $table->string('purpose', 30)->index();
            $table->time('in_time')->nullable();
            $table->time('out_time')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('location_fetched_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['mr_id', 'visit_date']);
            $table->index(['person_id', 'visit_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
