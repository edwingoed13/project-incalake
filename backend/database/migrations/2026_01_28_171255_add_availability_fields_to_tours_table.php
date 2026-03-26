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
            $table->boolean('require_availability')->default(false)->after('payment_method');
            $table->json('availability_data')->nullable()->after('require_availability');
            $table->json('blocks_data')->nullable()->after('availability_data');
            $table->json('offers_data')->nullable()->after('blocks_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn(['require_availability', 'availability_data', 'blocks_data', 'offers_data']);
        });
    }
};
