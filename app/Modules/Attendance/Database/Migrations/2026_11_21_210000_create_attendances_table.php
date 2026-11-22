<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per MR per day (docs/modules/attendance.md). The unique index is the
 * real guard against a double check-in: the service checks first and returns a
 * clean error, but a racing pair of requests from a flaky mobile connection is
 * stopped here regardless.
 *
 * Times are server-set and stored UTC. The device sends only coordinates, so
 * a phone with a wound-back clock cannot forge a shift.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mr_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');

            $table->timestamp('check_in_at')->nullable();
            $table->decimal('check_in_lat', 10, 7)->nullable();
            $table->decimal('check_in_lng', 10, 7)->nullable();

            $table->timestamp('check_out_at')->nullable();
            $table->decimal('check_out_lat', 10, 7)->nullable();
            $table->decimal('check_out_lng', 10, 7)->nullable();

            $table->timestamps();

            $table->unique(['mr_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
