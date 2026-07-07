<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data migration: the API admin gate (admin.api middleware) checks the users
 * `role` COLUMN (admin|staff), but the original seeder created the admin via
 * spatie assignRole('Super Admin') leaving the column at its 'customer'
 * default. Promote the known/seeded admins so enabling the middleware can't
 * lock the real operators out. Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) The seeded admin account.
        DB::table('users')
            ->where('email', 'admin@incalake.com')
            ->where('role', '!=', 'admin')
            ->update(['role' => 'admin']);

        // 2) Anyone holding a spatie admin-ish role but a non-admin column.
        if (Schema::hasTable('roles') && Schema::hasTable('model_has_roles')) {
            $roleIds = DB::table('roles')
                ->whereIn('name', ['Super Admin', 'Admin', 'admin'])
                ->pluck('id');

            if ($roleIds->isNotEmpty()) {
                $userIds = DB::table('model_has_roles')
                    ->whereIn('role_id', $roleIds)
                    ->where('model_type', 'App\\Models\\User')
                    ->pluck('model_id');

                if ($userIds->isNotEmpty()) {
                    DB::table('users')
                        ->whereIn('id', $userIds)
                        ->whereNotIn('role', ['admin', 'staff'])
                        ->update(['role' => 'admin']);
                }
            }
        }
    }

    public function down(): void
    {
        // Intentionally left blank — demoting admins automatically is riskier
        // than leaving the promotion in place.
    }
};
