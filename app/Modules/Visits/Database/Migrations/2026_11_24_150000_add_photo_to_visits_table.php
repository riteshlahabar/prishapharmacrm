<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The shopfront photo an MR takes on a visit (docs/modules/visits.md).
 *
 * Only the path is stored, on the **private** disk: a visit photo is
 * territory-scoped business data, so it is served by a controller that runs
 * the same policy as the visit itself, never from a public URL anyone could
 * guess. `photo_taken_at` is the device clock at the shutter, which sits
 * beside `location_fetched_at` for the same reason — proof the picture was
 * taken at the visit, not picked out of the gallery days later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->string('photo_path', 191)->nullable()->after('location_fetched_at');
            $table->timestamp('photo_taken_at')->nullable()->after('photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'photo_taken_at']);
        });
    }
};
