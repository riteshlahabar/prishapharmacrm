<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * `client_types` — what a client can be. Doctor, hospital and medical store
 * were a PHP enum until now, which meant adding "chemist" or "stockist" was a
 * deploy. They are rows here instead, editable from the panel.
 *
 * `persons.type` keeps the slug it already stored and is *not* turned into a
 * foreign key: the column is part of the mobile API contract both Flutter
 * apps decode (`docs/api/v1-common.md`), and an id would change that wire
 * format for no gain. The slug is therefore fixed once a type exists — the
 * panel shows it read-only on edit — while the label stays free to change.
 *
 * The table ships **empty**: Doctor, Hospital and Medical Store used to be
 * inserted here, and came back on every `migrate`, which left no way to run
 * the panel on types of your own. Add them from the Client Types screen
 * before the first client is saved — the client form has nothing to offer
 * until one type exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_types')) {
            Schema::create('client_types', function (Blueprint $table) {
                $table->id();
                // What persons.type holds. Fixed after creation.
                $table->string('slug', 40)->unique();
                $table->string('label', 80);
                // A Cuba badge name — the view renders `badge-light-<colour>`.
                $table->string('colour', 20)->default('primary');
                // Doctors have one; a medical store does not.
                $table->boolean('has_specialization')->default(false);
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        $now = now();

        // A deployment converted from the old `customers` table already holds
        // typed clients. Keep every one of those types resolvable rather than
        // leaving those clients with a blank type.
        if (Schema::hasTable('persons')) {
            $known = DB::table('client_types')->pluck('slug')->all();

            $orphans = DB::table('persons')
                ->whereNotNull('type')
                ->where('type', '!=', '')
                ->whereNotIn('type', $known)
                ->distinct()
                ->pluck('type');

            foreach ($orphans as $index => $slug) {
                DB::table('client_types')->insert([
                    'slug' => $slug,
                    'label' => Str::headline($slug),
                    'colour' => 'secondary',
                    'has_specialization' => false,
                    'is_active' => true,
                    'sort_order' => 100 + $index,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_types');
    }
};
