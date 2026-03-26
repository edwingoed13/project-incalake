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
        Schema::table('tours', function (Blueprint $table) {
            // Idioma principal del tour
            $table->foreignId('primary_language_id')->nullable()->after('code')->constrained('languages')->onDelete('set null');

            // Nuevos campos de horario y duración
            $table->time('departure_time')->nullable()->after('duration_hours')->comment('Hora de salida del tour');
            $table->enum('departure_period', ['AM', 'PM'])->default('AM')->after('departure_time')->comment('Periodo AM/PM');
            $table->integer('duration_quantity')->nullable()->after('departure_period')->comment('Cantidad de duración (ej: 2)');
            $table->enum('duration_unit', ['minutes', 'hours', 'days'])->default('hours')->after('duration_quantity')->comment('Unidad de duración');
            $table->string('timezone', 50)->default('America/Lima')->after('duration_unit')->comment('Zona horaria del tour');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropForeign(['primary_language_id']);
            $table->dropColumn([
                'primary_language_id',
                'departure_time',
                'departure_period',
                'duration_quantity',
                'duration_unit',
                'timezone'
            ]);
        });
    }
};
