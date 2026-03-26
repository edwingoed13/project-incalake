<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            'manage tours',
            'view tours',
            'create tours',
            'edit tours',
            'delete tours',
            'manage bookings',
            'view bookings',
            'manage users',
            'view reports',
            'manage settings',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Create Roles and Assign Permissions
        
        // Super Admin: Has everything
        $superAdminRole = Role::findOrCreate('Super Admin', 'web');
        // We don't necessarily need to assign all permissions to Super Admin if we use a Gate::before check,
        // but for safety we can do it.

        // Admin: Can manage almost everything except maybe system settings
        $adminRole = Role::findOrCreate('Admin', 'web');
        $adminRole->givePermissionTo([
            'manage tours',
            'view tours',
            'create tours',
            'edit tours',
            'delete tours',
            'manage bookings',
            'view bookings',
            'manage users',
            'view reports',
        ]);

        // Seller: Can manage bookings and view tours
        $sellerRole = Role::findOrCreate('Seller', 'web');
        $sellerRole->givePermissionTo([
            'view tours',
            'manage bookings',
            'view bookings',
        ]);

        // Guide: Can only view their assigned bookings/tours (example)
        $guideRole = Role::findOrCreate('Guide', 'web');
        $guideRole->givePermissionTo([
            'view tours',
            'view bookings',
        ]);

        // Assign Role to Existing Admin (if exists)
        $user = User::where('email', 'admin@incalake.com')->first();
        if ($user) {
            $user->assignRole($superAdminRole);
        }
        
        $userStaff = User::where('email', 'staff@incalake.com')->first();
        if ($userStaff) {
            $userStaff->assignRole($sellerRole);
        }
    }
}
