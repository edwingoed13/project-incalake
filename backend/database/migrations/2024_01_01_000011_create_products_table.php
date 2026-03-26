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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('subtitle', 255);
            $table->string('code', 64);
            $table->string('nearest_city', 255)->nullable();
            $table->string('nearest_airport', 255)->nullable();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->text('start_time')->nullable();
            $table->text('duration')->nullable();
            $table->integer('capacity')->default(999)->comment('Maximum people capacity (venue capacity)');
            $table->string('attachments', 128)->nullable()->comment('PDF version of the package');
            $table->foreignId('product_code_id')->constrained('product_codes')->onDelete('cascade');
            $table->boolean('status')->default(true)->comment('0=not visible, 1=visible on web');
            $table->string('policies', 10)->nullable();
            $table->string('booking_anticipation', 10)->nullable();
            $table->tinyInteger('data_requirement')->comment('1=ask data before purchase, 2=after purchase, 3=no passenger data required');
            $table->boolean('multiple_forms')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
