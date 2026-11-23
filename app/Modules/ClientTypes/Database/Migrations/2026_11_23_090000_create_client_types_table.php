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
 * The defaults are inserted here rather than left to a seeder. A deployment
 * that only ever runs `migrate` would otherwise come up with an empty type
 * master and no client able to be saved; the same trap the Reports
 * permissions fell into.
 */
return new class extends Migration
{
    /** Shipped with the product; `locked` keeps them from being deleted. */
    private const DEFAULTS = [
        ['slug' => 'doctor', 'label' => 'Doctor', 'colour' => 'primary', 'has_specialization' => true, 'sort_order' => 10],
        ['slug' => 'hospital', 'label' => 'Hospital', 'colour' => 'info', 'has_specialization' => false, 'sort_order' => 20],
        ['slug' => 'medical', 'label' => 'Medical Store', 'colour' => 'success', 'has_specialization' => false, 'sort_order' => 30],
    ];

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

        foreach (self::DEFAULTS as $row) {
            DB::table('client_types')->updateOrInsert(
                ['slug' => $row['slug']],
                $row + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            );
        }

        // A deployment converted from the old `customers` table can hold
        // types these three do not cover. Keep every one of them resolvable
        // rather than leaving those clients with a blank type.
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
