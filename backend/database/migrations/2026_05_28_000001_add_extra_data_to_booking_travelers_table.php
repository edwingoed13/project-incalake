<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_travelers', function (Blueprint $table) {
            // Admin-configurable per-traveler fields (birthdate, gender, hotel,
            // flights, weight, etc.) keyed by the same field codes the tour
            // booking-options use. Kept as JSON so the selectable set can change
            // without a schema migration each time.
            $table->json('extra_data')->nullable()->after('special_needs');
        });
    }

    public function down(): void
    {
        Schema::table('booking_travelers', function (Blueprint $table) {
            $table->dropColumn('extra_data');
        });
    }
};
