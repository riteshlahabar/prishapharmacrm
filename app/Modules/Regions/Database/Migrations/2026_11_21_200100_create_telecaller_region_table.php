<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A telecaller covers many regions; an MR covers one (`users.region_id`).
 * That asymmetry is deliberate — an MR physically travels a territory, a
 * telecaller works a phone list that an admin can widen at any time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telecaller_region', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('region_id')->constrained('regions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'region_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telecaller_region');
    }
};
