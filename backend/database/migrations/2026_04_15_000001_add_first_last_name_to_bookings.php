<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'customer_first_name')) {
                $table->string('customer_first_name', 100)->nullable()->after('customer_name');
            }
            if (!Schema::hasColumn('bookings', 'customer_last_name')) {
                $table->string('customer_last_name', 100)->nullable()->after('customer_first_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['customer_first_name', 'customer_last_name']);
        });
    }
};
