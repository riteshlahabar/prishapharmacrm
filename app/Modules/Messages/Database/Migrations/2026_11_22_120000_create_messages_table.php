<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * In-app messages, telecaller → MR (docs/modules/messages.md). **No Firebase**:
 * the MR app polls `unread-count`, so the indexes here are what keep that poll
 * cheap — `(to_user_id, read_at)` answers it from the index alone.
 *
 * Read state is a single nullable `read_at`. The planned schema had an
 * `is_read` flag beside it, but two columns that must agree eventually do not;
 * the API still exposes `is_read`, derived.
 *
 * `person_id` and `call_log_id` are optional context — "about this doctor",
 * "following this call". Both are nullOnDelete: losing the context must not
 * delete what someone said.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();

            $table->foreignId('person_id')->nullable()->constrained('persons')->nullOnDelete();
            $table->foreignId('call_log_id')->nullable()->constrained('call_logs')->nullOnDelete();

            $table->string('subject', 150);
            $table->text('body');
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            // The inbox: one recipient's messages, newest first.
            $table->index(['to_user_id', 'created_at']);
            // The poll: unread for one recipient, answered from the index.
            $table->index(['to_user_id', 'read_at']);
            $table->index(['from_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
