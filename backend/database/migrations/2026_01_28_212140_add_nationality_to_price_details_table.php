<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Guarded per column: a later migration also creates nationality_id, so
        // running the full stack from scratch (fresh local/CI DB) died here with
        // "1060 Duplicate column name". Prod was migrated incrementally and has
        // this recorded as run, so the guards only ever matter on a clean build.
        Schema::table('price_details', function (Blueprint $table) {
            if (!Schema::hasColumn('price_details', 'nationality_id')) {
                $table->foreignId('nationality_id')->nullable()->after('age_stage_id')->constrained('nationalities')->onDelete('cascade');
            }
            if (!Schema::hasColumn('price_details', 'age_min')) {
                $table->integer('age_min')->nullable()->after('nationality_id'); // Edad mínima para esta nacionalidad
            }
            if (!Schema::hasColumn('price_details', 'age_max')) {
                $table->integer('age_max')->nullable()->after('age_min'); // Edad máxima para esta nacionalidad
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('price_details', function (Blueprint $table) {
            $table->dropForeign(['nationality_id']);
            $table->dropColumn(['nationality_id', 'age_min', 'age_max']);
        });
    }
};
