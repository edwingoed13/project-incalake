<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which browser TAB wrote the draft. The whole team shares one admin account,
 * so "Admin guardó cambios mientras editabas" names the person you already
 * are — useless for deciding what to keep. The tab id lets the wizard tell
 * "your own save racing itself" (auto-resolve silently) apart from "another
 * tab or computer on this same account" (real conflict, now said in words
 * that make sense).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tour_revisions', 'updated_by_tab')) {
            return;
        }

        Schema::table('tour_revisions', function (Blueprint $table) {
            $table->string('updated_by_tab', 40)->nullable()->after('updated_by');
        });
    }

    public function down(): void
    {
        Schema::table('tour_revisions', function (Blueprint $table) {
            $table->dropColumn('updated_by_tab');
        });
    }
};
