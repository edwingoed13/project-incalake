<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Expand the booking_anticipation_unit enum to include 'minutes'.
        // We use raw SQL because Laravel's column modifier doesn't easily
        // alter enum values in MySQL/MariaDB.
        DB::statement("ALTER TABLE tours MODIFY COLUMN booking_anticipation_unit ENUM('minutes', 'hours', 'days') NOT NULL DEFAULT 'hours'");
    }

    public function down(): void
    {
        // Normalize any 'minutes' rows back to 'hours' before shrinking the enum.
        DB::table('tours')
            ->where('booking_anticipation_unit', 'minutes')
            ->update(['booking_anticipation_unit' => 'hours']);

        DB::statement("ALTER TABLE tours MODIFY COLUMN booking_anticipation_unit ENUM('hours', 'days') NOT NULL DEFAULT 'hours'");
    }
};
