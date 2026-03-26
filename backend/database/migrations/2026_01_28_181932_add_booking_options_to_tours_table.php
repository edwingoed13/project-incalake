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
            // Políticas
            $table->enum('policy_type', ['standard', 'custom'])->default('standard')->after('payment_method');
            $table->text('policy_description')->nullable()->after('policy_type');

            // Tiempo de anticipación para reserva
            $table->integer('booking_anticipation_quantity')->default(5)->after('policy_description');
            $table->enum('booking_anticipation_unit', ['hours', 'days'])->default('hours')->after('booking_anticipation_quantity');

            // Información operacional (JSON array con los campos requeridos)
            $table->json('operational_info_required')->nullable()->after('booking_anticipation_unit');

            // Información personal (JSON array con los campos requeridos)
            $table->json('personal_info_required')->nullable()->after('operational_info_required');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn([
                'policy_type',
                'policy_description',
                'booking_anticipation_quantity',
                'booking_anticipation_unit',
                'operational_info_required',
                'personal_info_required'
            ]);
        });
    }
};
