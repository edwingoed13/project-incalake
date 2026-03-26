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
        Schema::create('age_stages', function (Blueprint $table) {
            $table->id();
            $table->string('description', 45);
            $table->integer('min_age');
            $table->integer('max_age')->default(999);
            $table->boolean('editable')->default(true)->comment('Can be edited or not');
            $table->text('translations')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('age_stages');
    }
};
