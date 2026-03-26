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
        Schema::create('booking_pickup_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->enum('pickup_type', ['hotel_within_radius', 'hotel_extra_charge', 'meeting_point'])->nullable();
            $table->string('hotel_name')->nullable();
            $table->text('hotel_address')->nullable();
            $table->string('hotel_place_id')->nullable(); // Google Place ID
            $table->decimal('hotel_lat', 10, 8)->nullable();
            $table->decimal('hotel_lng', 11, 8)->nullable();
            $table->decimal('distance_from_center', 5, 2)->nullable(); // en km
            $table->decimal('extra_pickup_cost', 10, 2)->default(0);
            $table->enum('final_choice', ['hotel_pickup', 'meeting_point', 'pending'])->default('pending');
            $table->boolean('requires_logistics_approval')->default(false);
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->nullable();
            $table->text('logistics_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->index('booking_id');
            $table->index('pickup_type');
            $table->index('requires_logistics_approval');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_pickup_details');
    }
};
