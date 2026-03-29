<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_translations', function (Blueprint $table) {
            $table->string('youtube_url', 500)->nullable()->after('cancellation_policy');
            // JSON array: [{media_id: 1, alt_text: "...", title_text: "..."}]
            $table->json('media_texts')->nullable()->after('youtube_url');
        });
    }

    public function down(): void
    {
        Schema::table('tour_translations', function (Blueprint $table) {
            $table->dropColumn(['youtube_url', 'media_texts']);
        });
    }
};
