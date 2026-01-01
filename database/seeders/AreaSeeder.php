<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areas = [
            [
                'name' => 'Tecnología de la información',
                'description' => 'Programas de software, redes, infraestructura, soporte TI y transformación digital.'
            ],
            [
                'name' => 'Administración y gestión empresarial',
                'description' => 'Programas administrativos, gestión empresarial, talento humano, archivo y asistencia administrativa.'
            ],
            [
                'name' => 'Contabilidad y finanzas',
                'description' => 'Programas de contabilidad, costos, finanzas y apoyo a procesos contables.'
            ],
            [
                'name' => 'Salud y bienestar',
                'description' => 'Programas asociados a salud, apoyo clínico/administrativo en salud y bienestar.'
            ],
            [
                'name' => 'Mantenimiento industrial',
                'description' => 'Mantenimiento mecánico/eléctrico, automatización y operación de equipos industriales.'
            ],
            [
                'name' => 'Construcción',
                'description' => 'Obra civil, edificaciones, seguridad en alturas y procesos relacionados.'
            ],
            [
                'name' => 'Agroindustria',
                'description' => 'Producción agropecuaria, agroindustria, transformación y procesos rurales.'
            ],
            [
                'name' => 'Turismo y gastronomía',
                'description' => 'Servicios turísticos, hotelería, cocina, panadería y atención al cliente.'
            ],
            [
                'name' => 'Logística y transporte',
                'description' => 'Almacenamiento, inventarios, cadena de suministro, distribución y operación logística.'
            ],
            [
                'name' => 'Comercio y ventas',
                'description' => 'Mercadeo, ventas, servicio al cliente, punto de venta y comercio electrónico.'
            ],
            [
                'name' => 'Diseño y comunicaciones',
                'description' => 'Diseño gráfico, multimedia, producción audiovisual y comunicación digital.'
            ],
            [
                'name' => 'Seguridad y salud en el trabajo',
                'description' => 'Prevención de riesgos, inspecciones, normas SST y cultura de seguridad.'
            ],
        ];

        foreach ($areas as $area) {
            Area::create($area);
        }
    }
}
