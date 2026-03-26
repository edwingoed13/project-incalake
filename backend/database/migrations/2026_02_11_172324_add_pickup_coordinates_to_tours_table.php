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
            // Meeting point coordinates
            $table->decimal('meeting_point_lat', 10, 7)->nullable()->after('meeting_point_description');
            $table->decimal('meeting_point_lng', 10, 7)->nullable()->after('meeting_point_lat');

            // Pickup center coordinates and radius
            $table->decimal('pickup_center_lat', 10, 7)->nullable()->after('pickup_location_description');
            $table->decimal('pickup_center_lng', 10, 7)->nullable()->after('pickup_center_lat');
            $table->decimal('pickup_radius_km', 5, 2)->nullable()->after('pickup_center_lng');

            // Dropoff description
            $table->text('dropoff_location_description')->nullable()->after('pickup_radius_km');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn([
                'meeting_point_lat',
                'meeting_point_lng',
                'pickup_center_lat',
                'pickup_center_lng',
                'pickup_radius_km',
                'dropoff_location_description'
            ]);
        });
    }
};