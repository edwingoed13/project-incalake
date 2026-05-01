<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            if (!Schema::hasColumn('tours', 'duration_minutes')) {
                $table->integer('duration_minutes')->nullable()->after('duration_hours')
                    ->comment('Minutes part of the duration (alongside duration_days and duration_hours)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            if (Schema::hasColumn('tours', 'duration_minutes')) {
                $table->dropColumn('duration_minutes');
            }
        });
    }
};
