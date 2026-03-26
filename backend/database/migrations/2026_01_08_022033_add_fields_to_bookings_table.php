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
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->after('code');
            $table->unsignedBigInteger('user_id')->nullable()->after('product_id');
            $table->string('customer_name')->after('user_id');
            $table->string('customer_email')->after('customer_name');
            $table->string('customer_phone')->nullable()->after('customer_email');
            $table->integer('number_of_people')->default(1)->after('customer_phone');
            $table->date('booking_date')->after('number_of_people');
            $table->decimal('total_price', 10, 2)->default(0)->after('booking_date');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending')->after('total_price');
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending')->after('status');
            $table->text('notes')->nullable()->after('payment_status');

            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn([
                'product_id',
                'user_id',
                'customer_name',
                'customer_email',
                'customer_phone',
                'number_of_people',
                'booking_date',
                'total_price',
                'status',
                'payment_status',
                'notes',
            ]);
        });
    }
};
