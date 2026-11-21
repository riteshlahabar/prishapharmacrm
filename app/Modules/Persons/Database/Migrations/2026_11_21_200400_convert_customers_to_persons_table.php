<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `customers` becomes `persons` (docs/schema.md). Written as a rename plus
 * column changes rather than create-and-drop so an existing deployment keeps
 * its rows: the cPanel database may hold real contacts, and this container's
 * copy being empty is not evidence that theirs is.
 *
 * The `customer_*` prefixes go — the table no longer describes customers —
 * and the pharma columns arrive: who they are, which territory they sit in,
 * and which MR and telecaller own the relationship.
 */
return new class extends Migration
{
    public function up(): void
    {
        // A fresh install has no legacy table; a live one does.
        if (Schema::hasTable('customers') && ! Schema::hasTable('persons')) {
            Schema::rename('customers', 'persons');
        }

        if (! Schema::hasTable('persons')) {
            Schema::create('persons', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        Schema::table('persons', function (Blueprint $table) {
            foreach ([
                'customer_name' => 'name',
                'customer_email' => 'email',
                'customer_phone' => 'mobile',
                'customer_address' => 'address',
                'customer_city' => 'city',
            ] as $from => $to) {
                if (Schema::hasColumn('persons', $from) && ! Schema::hasColumn('persons', $to)) {
                    $table->renameColumn($from, $to);
                }
            }
        });

        Schema::table('persons', function (Blueprint $table) {
            // Dropped with the generic CRM: nothing in scope reads them.
            foreach (['customer_state', 'customer_zip', 'customer_country', 'customer_website'] as $column) {
                if (Schema::hasColumn('persons', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('persons', function (Blueprint $table) {
            if (! Schema::hasColumn('persons', 'name')) {
                $table->string('name');
            }
            if (! Schema::hasColumn('persons', 'email')) {
                $table->string('email')->nullable();
            }
            if (! Schema::hasColumn('persons', 'mobile')) {
                $table->string('mobile', 20)->nullable();
            }
            if (! Schema::hasColumn('persons', 'address')) {
                $table->text('address')->nullable();
            }
            if (! Schema::hasColumn('persons', 'city')) {
                $table->string('city')->nullable();
            }

            $table->string('type', 20)->default('doctor')->index()->after('name');
            $table->string('specialization')->nullable()->after('city');
            $table->foreignId('region_id')->nullable()->after('specialization')
                ->constrained('regions')->nullOnDelete();
            // nullOnDelete, not cascade: losing the rep must not lose the contact.
            $table->foreignId('assigned_mr_id')->nullable()->after('region_id')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_telecaller_id')->nullable()->after('assigned_mr_id')
                ->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true)->index()->after('assigned_telecaller_id');

            $table->index(['region_id', 'type']);
        });
    }

    public function down(): void
    {
        // Constraints first: MySQL uses the (region_id, type) index to satisfy
        // the region_id foreign key and refuses to drop it while that holds.
        Schema::table('persons', function (Blueprint $table) {
            $table->dropForeign(['assigned_telecaller_id']);
            $table->dropForeign(['assigned_mr_id']);
            $table->dropForeign(['region_id']);
        });

        Schema::table('persons', function (Blueprint $table) {
            $table->dropIndex(['region_id', 'type']);
        });

        Schema::table('persons', function (Blueprint $table) {
            $table->dropColumn([
                'type', 'specialization', 'is_active',
                'region_id', 'assigned_mr_id', 'assigned_telecaller_id',
            ]);
        });

        Schema::table('persons', function (Blueprint $table) {
            $table->renameColumn('name', 'customer_name');
            $table->renameColumn('email', 'customer_email');
            $table->renameColumn('mobile', 'customer_phone');
            $table->renameColumn('address', 'customer_address');
            $table->renameColumn('city', 'customer_city');
            $table->string('customer_state')->nullable();
            $table->string('customer_zip')->nullable();
            $table->string('customer_country')->nullable();
            $table->string('customer_website')->nullable();
        });

        Schema::rename('persons', 'customers');
    }
};
