<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_translations', function (Blueprint $table) {
            if (!Schema::hasColumn('tour_translations', 'custom_sections')) {
                $table->json('custom_sections')->nullable()->after('cancellation_policy')
                    ->comment('Per-translation list of additional rich-text sections [{title, content}]');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tour_translations', function (Blueprint $table) {
            if (Schema::hasColumn('tour_translations', 'custom_sections')) {
                $table->dropColumn('custom_sections');
            }
        });
    }
};
