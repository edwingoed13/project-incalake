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
        Schema::table('nationalities', function (Blueprint $table) {
            if (!Schema::hasColumn('nationalities', 'code')) {
                $table->string('code', 10)->nullable()->after('description');
            }
            if (!Schema::hasColumn('nationalities', 'order')) {
                $table->integer('order')->default(0)->after('code');
            }
            if (!Schema::hasColumn('nationalities', 'editable')) {
                $table->boolean('editable')->default(true)->after('order');
            }
            if (!Schema::hasColumn('nationalities', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nationalities', function (Blueprint $table) {
            $table->dropColumn(['code', 'order', 'editable']);
            $table->dropSoftDeletes();
        });
    }
};
