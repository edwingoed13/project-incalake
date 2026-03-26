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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 20)->unique()->comment('Unique booking code (e.g., BK-2026-0001)');

            // Tour information
            $table->foreignId('tour_id')->constrained('tours')->onDelete('restrict');
            $table->string('tour_title')->comment('Tour title at time of booking');
            $table->date('tour_date');
            $table->time('tour_time')->nullable();

            // Customer information
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            $table->string('customer_country', 2)->nullable()->comment('ISO country code');
            $table->text('customer_notes')->nullable();

            // Booking details
            $table->unsignedInteger('adults')->default(1);
            $table->unsignedInteger('children')->default(0);
            $table->unsignedInteger('infants')->default(0);
            $table->unsignedInteger('total_participants');

            // Pricing
            $table->string('currency', 3)->default('USD');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            // Payment information
            $table->enum('payment_method', ['culqi', 'paypal'])->comment('Payment gateway used');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('payment_id')->nullable()->comment('Transaction ID from payment gateway');
            $table->text('payment_data')->nullable()->comment('JSON with payment gateway response');
            $table->timestamp('paid_at')->nullable();

            // Booking status
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Pickup information (if applicable)
            $table->string('pickup_location')->nullable();
            $table->time('pickup_time')->nullable();

            // Additional data
            $table->json('participants_data')->nullable()->comment('Detailed participant information');
            $table->text('admin_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('booking_code');
            $table->index('customer_email');
            $table->index('tour_date');
            $table->index('payment_status');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
