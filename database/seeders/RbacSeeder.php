<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            'sucursales.view',
            'sucursales.create',
            'sucursales.edit',
            'sucursales.delete',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $super = Role::firstOrCreate(['name' => 'super_admin']);
        $admin = Role::firstOrCreate(['name' => 'admin']);

        $admin->syncPermissions($permissions);

        // Crear o actualizar super admin
        $user = User::firstOrCreate(
            ['email' => 'superadmin@chilierp.local'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Admin1234!'),
            ]
        );

        $user->syncRoles([$super]);
    }
}