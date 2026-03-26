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
        Schema::table('price_details', function (Blueprint $table) {
            $table->foreignId('nationality_id')->nullable()->after('age_stage_id')->constrained('nationalities')->onDelete('cascade');
            $table->integer('age_min')->nullable()->after('nationality_id'); // Edad mínima para esta nacionalidad
            $table->integer('age_max')->nullable()->after('age_min'); // Edad máxima para esta nacionalidad
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
