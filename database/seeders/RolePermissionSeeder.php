<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::findByName('Administrador');
        $gestor = Role::findByName('Gestor de Fichas');
        $instructor = Role::findByName('Instructor');

        $permissions = Permission::all();

        $adminRole->syncPermissions($permissions);
        $gestor->syncPermissions($permissions);
        $instructor->syncPermissions($permissions);
    }
}
