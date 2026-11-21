<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 added `users.region_id` with an index but no constraint, because
 * `regions` did not exist yet. It does now, so the constraint goes on.
 *
 * nullOnDelete, not cascade: deleting a region must never delete the people
 * working it. (RegionService refuses that delete anyway; this is the backstop
 * for anyone going in through SQL.)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Any id left over from before the constraint would block it.
        DB::table('users')
            ->whereNotNull('region_id')
            ->whereNotIn('region_id', fn ($q) => $q->select('id')->from('regions'))
            ->update(['region_id' => null]);

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('region_id')->references('id')->on('regions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
        });
    }
};
