<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a telecaller talked through on a call: a product and how many boxes.
 * The call-side twin of `visit_products`, with the same rules — a snapshot
 * cost, at most one line per product, and a product that cannot be deleted
 * out from under a record.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_log_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_log_id')->constrained('call_logs')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedInteger('box_qty');
            $table->decimal('unit_cost', 10, 2);
            $table->timestamps();

            $table->unique(['call_log_id', 'product_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_log_products');
    }
};
