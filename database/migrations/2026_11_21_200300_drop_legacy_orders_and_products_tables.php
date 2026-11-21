<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `products` and `orders` came from the generic CRM this project started as.
 * Prisha Pharma tracks visits and calls, not stock and invoices, so they are
 * dropped rather than carried (docs/roadmap.md, "Dropped from scope").
 *
 * Order matters: `orders` holds the foreign keys onto `customers`, and the
 * next migration renames that table. Dropping `orders` first means the rename
 * does not have to drag a constraint along with it.
 *
 * Lives in database/migrations rather than a module: it retires code that
 * never belonged to one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
    }

    /**
     * Recreates the structure so the migration is reversible. The rows are
     * gone for good — this is a scope decision, not a reshuffle.
     */
    public function down(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->string('product_code')->unique();
            $table->string('generic_name')->nullable();
            $table->string('category')->nullable();
            $table->string('manufacturer')->nullable();
            $table->decimal('mrp', 10, 2)->default(0);
            $table->decimal('selling_price', 10, 2)->default(0);
            $table->integer('stock_quantity')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->unique();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->date('order_date')->useCurrent();
            $table->enum('status', ['pending', 'confirmed', 'delivered', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }
};
