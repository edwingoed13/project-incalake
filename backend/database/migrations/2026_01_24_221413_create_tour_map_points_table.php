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
        Schema::create('tour_map_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained('tours')->onDelete('cascade');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('coordinates', 100); // "lat,lng"
            $table->enum('type', [
                'punto_parada',      // Punto de Parada
                'restaurant',        // Restaurant
                'lugar_turistico',   // Lugar Turístico
                'aeropuerto',        // Aeropuerto
                'estacion_tren',     // Estación de Tren
                'terminal_terrestre',// Terminal Terrestre (Bus)
                'museo',             // Museo
                'punto_reunion',     // Punto de Reunión
                'otro'               // Otro
            ])->default('punto_parada');
            $table->integer('order')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tour_map_points');
    }
};
