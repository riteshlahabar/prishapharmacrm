<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 columns on `users` (docs/schema.md).
 *
 * `role` is nullable on purpose: rows that predate Phase 1 must not be
 * silently promoted to a role, and a user with no role can neither sign into
 * the panel nor obtain an app token. Validation makes it required everywhere a
 * user is created or edited.
 *
 * `region_id` carries no foreign key yet — `regions` arrives in Phase 2, which
 * adds the constraint. The index is here so the later migration only adds the
 * constraint.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile', 20)->nullable()->unique()->after('email');
            $table->string('role', 20)->nullable()->index()->after('mobile');
            $table->unsignedBigInteger('region_id')->nullable()->index()->after('role');
            $table->boolean('is_active')->default(true)->index()->after('region_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['region_id']);
            $table->dropIndex(['role']);
            $table->dropUnique(['mobile']);
            $table->dropColumn(['mobile', 'role', 'region_id', 'is_active']);
        });
    }
};
