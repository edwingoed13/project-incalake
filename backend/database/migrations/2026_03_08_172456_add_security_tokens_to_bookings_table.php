<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Token de seguridad de 8 caracteres para el código visible
            $table->string('security_token', 8)->nullable()->after('booking_code');

            // Token largo de confirmación para URLs en emails (64 chars)
            $table->string('confirmation_token', 64)->unique()->nullable()->after('security_token');

            // Expiración del token de confirmación
            $table->timestamp('confirmation_token_expires_at')->nullable()->after('confirmation_token');

            // Índices para búsquedas rápidas
            $table->index('security_token');
            $table->index('confirmation_token');
        });

        // Generar tokens para bookings existentes
        $bookings = DB::table('bookings')->whereNull('security_token')->get();

        foreach ($bookings as $booking) {
            DB::table('bookings')
                ->where('id', $booking->id)
                ->update([
                    'security_token' => bin2hex(random_bytes(4)),
                    'confirmation_token' => Str::random(64),
                    'confirmation_token_expires_at' => now()->addYear()
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['security_token']);
            $table->dropIndex(['confirmation_token']);
            $table->dropColumn(['security_token', 'confirmation_token', 'confirmation_token_expires_at']);
        });
    }
};
