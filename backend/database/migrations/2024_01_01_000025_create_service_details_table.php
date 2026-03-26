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
        Schema::create('service_details', function (Blueprint $table) {
            $table->id();
            $table->dateTime('service_date')->nullable()->comment('Service execution date');
            $table->integer('quantity')->nullable();
            $table->decimal('total_price', 8, 3)->default(0.000)->comment('Total service price');
            $table->decimal('discount', 8, 3)->default(0.000)->comment('Service discount');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_details');
    }
};
