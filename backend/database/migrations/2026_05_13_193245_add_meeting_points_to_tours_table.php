<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            // Array of meeting points. Each entry:
            //   { id, lat, lng, descriptions: { es, en, ... } }
            // The legacy meeting_point_lat/lng/description fields are kept in sync with the
            // first item for backward compatibility with anything that still reads them.
            $table->json('meeting_points')->nullable()->after('meeting_point_lng');
        });
    }

    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn('meeting_points');
        });
    }
};
