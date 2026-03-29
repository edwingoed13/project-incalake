<?php

namespace App\Traits;

trait HasPermissions
{
    /**
     * Check if user has a specific permission
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        $role = $this->role ?? 'customer';
        $permissions = config("permissions.roles.{$role}.permissions", []);

        return $permissions[$permission] ?? false;
    }

    /**
     * Check if user has any of the given custom permissions
     *
     * @param array $permissions
     * @return bool
     */
    public function hasAnyCustomPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the given permissions
     *
     * @param array $permissions
     * @return bool
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all permissions for the user's role
     *
     * @return array
     */
    public function getPermissions(): array
    {
        $role = $this->role ?? 'customer';
        return config("permissions.roles.{$role}.permissions", []);
    }

    /**
     * Check if user is admin
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is staff
     *
     * @return bool
     */
    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    /**
     * Check if user is customer
     *
     * @return bool
     */
    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }
}
