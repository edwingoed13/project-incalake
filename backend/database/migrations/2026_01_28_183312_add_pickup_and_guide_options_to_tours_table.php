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
            // Opciones de recojo
            $table->enum('pickup_type', ['meeting_point', 'hotel_pickup'])->default('meeting_point')->after('personal_info_required');
            $table->text('meeting_point_description')->nullable()->after('pickup_type');
            $table->text('pickup_location_description')->nullable()->after('meeting_point_description');
            $table->text('dropoff_location_description')->nullable()->after('pickup_location_description');

            // Opciones de guía
            $table->enum('guide_type', ['live_guide', 'audio_guide', 'informative_brochures', 'no_guide', 'none'])->default('live_guide')->after('dropoff_location_description');
            $table->json('guide_languages')->nullable()->after('guide_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn([
                'pickup_type',
                'meeting_point_description',
                'pickup_location_description',
                'dropoff_location_description',
                'guide_type',
                'guide_languages'
            ]);
        });
    }
};
