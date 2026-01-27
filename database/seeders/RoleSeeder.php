<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'code' => 'ADMIN',
                'name' => 'Administrador',
                'description' => 'Acceso total al sistema; administra usuarios, roles/permisos y configuración general.',
                'guard_name' => 'web',
            ],
            [
                'code' => 'GESTOR_FICHAS',
                'name' => 'Gestor de Fichas',
                'description' => 'Gestiona fichas y sus asignaciones (instructores/aprendices) y ajustes operativos relacionados.',
                'guard_name' => 'web',
            ],
            [
                'code' => 'INSTRUCTOR',
                'name' => 'Instructor',
                'description' => 'Opera el sistema en las fichas asignadas (asistencia/procesos); sin fichas asignadas no gestiona información.',
                'guard_name' => 'web',
            ],
            [
                'code' => 'APRENDIZ',
                'name' => 'Aprendiz',
                'description' => 'No usa el panel del sistema; registra asistencia mediante escaneo u otro mecanismo definido.',
                'guard_name' => 'web',
            ],
            [
                'code' => 'PENDIENTE',
                'name' => 'Pendiente',
                'description' => 'Usuario sin aprobación; acceso mínimo (login/perfil/logout) sin acceso a módulos operativos.',
                'guard_name' => 'web',
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
