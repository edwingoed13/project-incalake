<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'tax_percentage')) {
                $table->decimal('tax_percentage', 5, 2)->default(0)->after('discount');
            }
            if (!Schema::hasColumn('bookings', 'tax_amount')) {
                $table->decimal('tax_amount', 10, 2)->default(0)->after('tax_percentage');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['tax_percentage', 'tax_amount']);
        });
    }
};
