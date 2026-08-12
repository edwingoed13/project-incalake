<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            LanguageSeeder::class,
            NationalitySeeder::class,
            AgeStageSeeder::class,
            CategorySeeder::class,
            RoleAndPermissionSeeder::class,
        ]);

        // Create default admin user if not exists.
        //
        // role='admin' is what actually grants access (EnsureAdminApi reads
        // canAccessAdminPanel, which reads this column). This used to call
        // assignRole('Super Admin'), a Spatie method User no longer has, so a
        // fresh `db:seed` died here and left the install without an admin —
        // nobody could log in.
        $admin = \App\Models\User::firstOrCreate(
            ['email' => 'admin@incalake.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
            ]
        );
        $admin->forceFill(['role' => 'admin'])->save();
    }
}
