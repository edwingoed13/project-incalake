<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a tour define its hotel-pickup coverage as a drawn area instead of a
 * circle. A radius cannot follow streets, so "we pick up in these blocks"
 * could only be approximated by a circle big enough to include places the
 * driver would not actually go.
 *
 * Additive on purpose: pickup_center_lat/lng and pickup_radius_km stay, and
 * pickup_area_type defaults to 'radius', so every existing tour keeps behaving
 * exactly as before and nothing needs migrating.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            if (!Schema::hasColumn('tours', 'pickup_area_type')) {
                $table->enum('pickup_area_type', ['radius', 'polygon'])
                    ->default('radius')
                    ->after('pickup_radius_km');
            }
            if (!Schema::hasColumn('tours', 'pickup_area')) {
                // One ring [{lat,lng},…] or several [[{lat,lng},…],…], so a
                // tour can cover two separate neighbourhoods.
                $table->json('pickup_area')->nullable()->after('pickup_area_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            foreach (['pickup_area', 'pickup_area_type'] as $column) {
                if (Schema::hasColumn('tours', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
