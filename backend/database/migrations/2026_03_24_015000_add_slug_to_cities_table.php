<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add slug column as nullable
        Schema::table('cities', function (Blueprint $table) {
            $table->string('slug', 100)->nullable()->after('name');
        });

        // Step 2: Populate slugs for existing cities
        DB::table('cities')->get()->each(function ($city) {
            $slug = Str::slug($city->name);
            DB::table('cities')
                ->where('id', $city->id)
                ->update(['slug' => $slug]);
        });

        // Step 3: Make slug unique and non-nullable
        Schema::table('cities', function (Blueprint $table) {
            $table->string('slug', 100)->nullable(false)->unique()->change();
        });

        // Add index
        Schema::table('cities', function (Blueprint $table) {
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropColumn('slug');
        });
    }
};
