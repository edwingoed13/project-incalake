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
            // Tasas e impuestos - porcentaje que se agrega al precio final
            $table->decimal('tax_percentage', 5, 2)->default(7.00)->after('booking_anticipation_hours')
                ->comment('Porcentaje de tasas e impuestos a aplicar al precio final');

            // Porcentaje de adelanto - primera cuota del pago
            $table->integer('advance_payment_percentage')->default(100)->after('tax_percentage')
                ->comment('Porcentaje de primera cuota/adelanto (100% = pago completo, menor = pago parcial)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn(['tax_percentage', 'advance_payment_percentage']);
        });
    }
};
