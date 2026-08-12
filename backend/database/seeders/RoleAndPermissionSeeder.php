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

        // Access is granted by users.role, not by the Spatie roles above.
        // User dropped the HasRoles trait when the project moved to the column
        // (see EnsureAdminApi / canAccessAdminPanel), so assignRole() no longer
        // exists and this seeder threw BadMethodCallException — which aborted
        // `php artisan db:seed` before it could create anything else.
        //
        // The Spatie roles and permissions are still created above because the
        // tables and package are still installed and nothing has decided to
        // remove them, but they currently grant nothing on their own.
        $user = User::where('email', 'admin@incalake.com')->first();
        if ($user) {
            $user->forceFill(['role' => 'admin'])->save();
        }

        $userStaff = User::where('email', 'staff@incalake.com')->first();
        if ($userStaff) {
            $userStaff->forceFill(['role' => 'staff'])->save();
        }
    }
}
