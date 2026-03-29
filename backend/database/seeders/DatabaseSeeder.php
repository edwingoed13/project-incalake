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

        // Create default admin user if not exists
        $admin = \App\Models\User::firstOrCreate(
            ['email' => 'admin@incalake.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
            ]
        );
        $admin->assignRole('Super Admin');
    }
}
