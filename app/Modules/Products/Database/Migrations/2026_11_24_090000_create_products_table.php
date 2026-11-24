<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `products` — the medical products an MR details on a visit and a telecaller
 * talks through on a call.
 *
 * The name is reused: a `products` table came with the generic CRM this
 * project started as and was dropped in Phase 2 (that migration recreates it
 * on rollback, which is why this one is written to be safe either way). The
 * columns have nothing in common with it — this is a short master of what we
 * sell, not the old catalogue.
 *
 * `box_qty` is stock on hand and is the admin's number alone: recording a
 * product on a visit or a call does **not** move it. The apps report what was
 * discussed, they are not a dispatch system.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            Schema::drop('products');
        }

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Boxes on hand. Unsigned: stock cannot be negative, and there is
            // nothing in the product that would make a negative meaningful.
            $table->unsignedInteger('box_qty')->default(0);
            // Price of one box. decimal, never float: money.
            $table->decimal('cost', 10, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
