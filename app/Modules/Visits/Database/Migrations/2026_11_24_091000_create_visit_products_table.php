<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What an MR detailed on a visit: a product and how many boxes of it. A
 * visit may carry none, one, or several.
 *
 * `unit_cost` is a snapshot, not a join. The product's price moves; what the
 * rep quoted that day does not, and a visit read back a year later has to
 * show the number that was on the screen at the time.
 *
 * `restrictOnDelete` on the product mirrors `visits.person_id`: deleting a
 * product must not quietly rewrite the record of having offered it. The
 * Products module refuses the delete with a readable message first.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedInteger('box_qty');
            $table->decimal('unit_cost', 10, 2);
            $table->timestamps();

            // One line per product per visit: two rows for the same product
            // is a double entry, not two different facts.
            $table->unique(['visit_id', 'product_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_products');
    }
};
