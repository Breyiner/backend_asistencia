<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::create([
            'code' => 'ADMIN',
            'name' => 'Administrador',
            'description' => 'Acceso total al sistema; administra usuarios, roles/permisos y configuración general.',
            'guard_name' => 'web',
        ]);

        Role::create([
            'code' => 'COORDINADOR',
            'name' => 'Coordinador',
            'description' => 'Coordina áreas y supervisa la operación dentro de las áreas asignadas.',
            'guard_name' => 'web',
        ]);

        Role::create([
            'code' => 'GESTOR_FICHAS',
            'name' => 'Gestor de Fichas',
            'description' => 'Gestiona fichas y sus asignaciones (instructores/aprendices) y ajustes operativos relacionados.',
            'guard_name' => 'web',
        ]);

        Role::create([
            'code' => 'INSTRUCTOR',
            'name' => 'Instructor',
            'description' => 'Opera el sistema en las fichas asignadas (asistencia/procesos).',
            'guard_name' => 'web',
        ]);

        Role::create([
            'code' => 'APRENDIZ',
            'name' => 'Aprendiz',
            'description' => 'No usa el panel del sistema; registra asistencia mediante escaneo u otro mecanismo definido.',
            'guard_name' => 'web',
        ]);

        Role::create([
            'code' => 'PENDIENTE',
            'name' => 'Pendiente',
            'description' => 'Usuario sin aprobación.',
            'guard_name' => 'web',
        ]);

        Role::create([
            'code' => 'SCANNER',
            'name' => 'Escáner',
            'description' => 'Usuario que escanea códigos de barras para registrar asistencia.',
            'guard_name' => 'web',
        ]);
    }
}
