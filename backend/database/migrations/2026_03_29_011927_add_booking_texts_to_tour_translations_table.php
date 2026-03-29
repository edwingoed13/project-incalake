<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_translations', function (Blueprint $table) {
            $table->json('booking_texts')->nullable()->after('media_texts');
        });
    }

    public function down(): void
    {
        Schema::table('tour_translations', function (Blueprint $table) {
            $table->dropColumn('booking_texts');
        });
    }
};
