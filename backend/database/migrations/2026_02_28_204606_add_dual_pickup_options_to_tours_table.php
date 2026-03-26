<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            // Add new boolean columns for dual pickup options
            $table->boolean('enable_meeting_point')->default(false)->after('personal_info_required');
            $table->boolean('enable_hotel_pickup')->default(false)->after('enable_meeting_point');
        });

        // Migrate existing data from pickup_type to new columns
        DB::table('tours')->where('pickup_type', 'meeting_point')->update([
            'enable_meeting_point' => true,
            'enable_hotel_pickup' => false,
        ]);

        DB::table('tours')->where('pickup_type', 'hotel_pickup')->update([
            'enable_meeting_point' => false,
            'enable_hotel_pickup' => true,
        ]);

        // For 'both' option if it exists, enable both
        DB::table('tours')->where('pickup_type', 'both')->update([
            'enable_meeting_point' => true,
            'enable_hotel_pickup' => true,
        ]);

        // Optional: Drop the old pickup_type column after migration
        // Commented out for safety - uncomment after verifying data migration
        // Schema::table('tours', function (Blueprint $table) {
        //     $table->dropColumn('pickup_type');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add the pickup_type column if it was dropped
        // Schema::table('tours', function (Blueprint $table) {
        //     $table->string('pickup_type', 50)->nullable()->after('personal_info_required');
        // });

        // Migrate data back from new columns to pickup_type
        // DB::table('tours')->where('enable_meeting_point', true)->where('enable_hotel_pickup', true)->update([
        //     'pickup_type' => 'both',
        // ]);
        // DB::table('tours')->where('enable_meeting_point', true)->where('enable_hotel_pickup', false)->update([
        //     'pickup_type' => 'meeting_point',
        // ]);
        // DB::table('tours')->where('enable_meeting_point', false)->where('enable_hotel_pickup', true)->update([
        //     'pickup_type' => 'hotel_pickup',
        // ]);

        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn(['enable_meeting_point', 'enable_hotel_pickup']);
        });
    }
};
