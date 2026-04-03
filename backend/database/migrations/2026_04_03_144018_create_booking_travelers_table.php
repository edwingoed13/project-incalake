<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_travelers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('full_name', 150);
            $table->string('nationality', 80)->nullable();
            $table->string('doc_type', 20)->default('passport'); // passport, dni
            $table->string('doc_number', 50)->nullable();
            $table->string('age_group', 20)->default('adult'); // adult, child, infant
            $table->text('special_needs')->nullable();
            $table->boolean('is_leader')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index('booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_travelers');
    }
};
