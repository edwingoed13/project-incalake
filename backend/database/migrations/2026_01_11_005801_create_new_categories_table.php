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
        Schema::create('categories_new', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique()->comment('Código único tipo slug: turismo-aventura');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Índices para optimización
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories_new');
    }
};
