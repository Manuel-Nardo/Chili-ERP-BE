<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web'; // importante que coincida con tu guard

        $perms = [
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
        ];

        foreach ($perms as $perm) {
            Permission::findOrCreate($perm, $guard);
        }

        $role = Role::findOrCreate('super_admin', $guard);
        $role->syncPermissions($perms);
    }
}