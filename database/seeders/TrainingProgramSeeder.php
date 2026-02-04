<?php

namespace Database\Seeders;

use App\Models\TrainingProgram;
use Illuminate\Database\Seeder;

class TrainingProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $programs = [
            // ========================================
            // PROGRAMAS TÉCNICOS (15 MESES)
            // qualification_level_id = 2
            // ========================================
            
            // MANTENIMIENTO INDUSTRIAL (area_id = 5)
            [
                'name' => 'Dibujo mecánico',
                'description' => 'Formación en técnicas de dibujo y diseño mecánico.',
                'duration' => 15,
                'qualification_level_id' => 2,
                'area_id' => 5,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Soldadura de productos metálicos en platina',
                'description' => 'Técnicas de soldadura aplicadas a productos metálicos.',
                'duration' => 15,
                'qualification_level_id' => 2,
                'area_id' => 5,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Instrumentación industrial',
                'description' => 'Instalación y calibración de instrumentos de medición industrial.',
                'duration' => 15,
                'qualification_level_id' => 2,
                'area_id' => 5,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Mantenimiento y ensamble de equipos electrónicos',
                'description' => 'Reparación y ensamblaje de dispositivos electrónicos.',
                'duration' => 15,
                'qualification_level_id' => 2,
                'area_id' => 5,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Mantenimiento de automatismos industriales',
                'description' => 'Mantenimiento de sistemas automatizados en procesos industriales.',
                'duration' => 15,
                'qualification_level_id' => 2,
                'area_id' => 5,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Operación en torno y fresadora',
                'description' => 'Manejo de máquinas herramientas para mecanizado de piezas.',
                'duration' => 15,
                'qualification_level_id' => 2,
                'area_id' => 5,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Mantenimiento de equipos de aire acondicionado y refrigeración',
                'description' => 'Instalación, mantenimiento y reparación de sistemas de climatización.',
                'duration' => 15,
                'qualification_level_id' => 2,
                'area_id' => 5,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Mantenimiento e instalación de sistemas solares fotovoltaicos',
                'description' => 'Instalación y mantenimiento de sistemas de energía solar.',
                'duration' => 15,
                'qualification_level_id' => 2,
                'area_id' => 5,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Mecánica de maquinaria industrial',
                'description' => 'Mantenimiento y reparación de maquinaria industrial.',
                'duration' => 15,
                'qualification_level_id' => 2,
                'area_id' => 5,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Electricista industrial',
                'description' => 'Instalación y mantenimiento de sistemas eléctricos industriales.',
                'duration' => 15,
                'qualification_level_id' => 2,
                'area_id' => 5,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Instalación de sistemas eléctricos residenciales y comerciales',
                'description' => 'Instalación y mantenimiento de sistemas eléctricos en edificaciones.',
                'duration' => 15,
                'qualification_level_id' => 2,
                'area_id' => 5,
                'coordinator_id' => 17
            ],

            // TECNOLOGÍA DE LA INFORMACIÓN (area_id = 1)
            [
                'name' => 'Integración de contenidos digitales',
                'description' => 'Desarrollo y gestión de contenidos digitales multimedia.',
                'duration' => 15,
                'qualification_level_id' => 2,
                'area_id' => 1,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Instalación y mantenimiento de redes inalámbricas',
                'description' => 'Configuración y mantenimiento de infraestructura de redes inalámbricas.',
                'duration' => 15,
                'qualification_level_id' => 2,
                'area_id' => 1,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Mantenimiento equipos de cómputo',
                'description' => 'Reparación y mantenimiento de hardware y software de computadores.',
                'duration' => 15,
                'qualification_level_id' => 2,
                'area_id' => 1,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Sistemas teleinformáticos',
                'description' => 'Administración de sistemas de telecomunicaciones e informática.',
                'duration' => 15,
                'qualification_level_id' => 2,
                'area_id' => 1,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Control de la seguridad digital',
                'description' => 'Protección de sistemas informáticos y gestión de seguridad digital.',
                'duration' => 15,
                'qualification_level_id' => 2,
                'area_id' => 1,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Programación de software',
                'description' => 'Desarrollo de aplicaciones y sistemas informáticos.',
                'duration' => 15,
                'qualification_level_id' => 2,
                'area_id' => 1,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Tratamiento de riesgos de ciberseguridad en MIPYMES',
                'description' => 'Gestión de riesgos de seguridad informática en pequeñas y medianas empresas.',
                'duration' => 15,
                'qualification_level_id' => 2,
                'area_id' => 1,
                'coordinator_id' => 17
            ],

            // LOGÍSTICA Y TRANSPORTE (area_id = 9)
            [
                'name' => 'Mantenimiento eléctrico y control electrónico de automotores',
                'description' => 'Diagnóstico y reparación de sistemas eléctricos y electrónicos automotrices.',
                'duration' => 15,
                'qualification_level_id' => 2,
                'area_id' => 9,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Mantenimiento de los motores diesel',
                'description' => 'Diagnóstico y reparación de motores de combustión diesel.',
                'duration' => 15,
                'qualification_level_id' => 2,
                'area_id' => 9,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Mantenimiento de motocicletas y motocarros',
                'description' => 'Diagnóstico y reparación de motocicletas y vehículos de dos ruedas.',
                'duration' => 15,
                'qualification_level_id' => 2,
                'area_id' => 9,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Control de movilidad, transporte y seguridad vial',
                'description' => 'Gestión de sistemas de movilidad y seguridad en el transporte.',
                'duration' => 15,
                'qualification_level_id' => 2,
                'area_id' => 9,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Mantenimiento de sistemas de propulsión eléctrica e híbrida automotriz',
                'description' => 'Mantenimiento de vehículos eléctricos e híbridos.',
                'duration' => 15,
                'qualification_level_id' => 2,
                'area_id' => 9,
                'coordinator_id' => 17
            ],

            // ========================================
            // PROGRAMAS TECNÓLOGOS (27 MESES)
            // qualification_level_id = 3
            // ========================================

            // SALUD Y BIENESTAR (area_id = 4)
            [
                'name' => 'Mantenimiento de equipo biomédico',
                'description' => 'Mantenimiento preventivo y correctivo de equipos médicos hospitalarios.',
                'duration' => 27,
                'qualification_level_id' => 3,
                'area_id' => 4,
                'coordinator_id' => 17
            ],

            // MANTENIMIENTO INDUSTRIAL (area_id = 5)
            [
                'name' => 'Implementación y mantenimiento de sistemas de instrumentación y control de procesos industriales',
                'description' => 'Gestión de sistemas de control automatizado en la industria.',
                'duration' => 27,
                'qualification_level_id' => 3,
                'area_id' => 5,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Mantenimiento electromecánico industrial',
                'description' => 'Mantenimiento integral de sistemas electromecánicos en plantas industriales.',
                'duration' => 27,
                'qualification_level_id' => 3,
                'area_id' => 5,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Automatización de sistemas mecatrónicos',
                'description' => 'Diseño e implementación de sistemas automatizados mecatrónicos.',
                'duration' => 27,
                'qualification_level_id' => 3,
                'area_id' => 5,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Desarrollo de sistemas electrónicos industriales',
                'description' => 'Diseño y construcción de circuitos y sistemas electrónicos para la industria.',
                'duration' => 27,
                'qualification_level_id' => 3,
                'area_id' => 5,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Electricidad industrial',
                'description' => 'Gestión de sistemas eléctricos en entornos industriales.',
                'duration' => 27,
                'qualification_level_id' => 3,
                'area_id' => 5,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Producción de componentes mecánicos con máquinas CNC',
                'description' => 'Fabricación de piezas mediante control numérico computarizado.',
                'duration' => 27,
                'qualification_level_id' => 3,
                'area_id' => 5,
                'coordinator_id' => 17
            ],

            // TECNOLOGÍA DE LA INFORMACIÓN (area_id = 1)
            [
                'name' => 'Implementación de redes y servicios de telecomunicaciones',
                'description' => 'Diseño e implementación de infraestructura de telecomunicaciones.',
                'duration' => 27,
                'qualification_level_id' => 3,
                'area_id' => 1,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Desarrollo de videojuegos y entornos interactivos',
                'description' => 'Creación de videojuegos y aplicaciones interactivas multimedia.',
                'duration' => 27,
                'qualification_level_id' => 3,
                'area_id' => 1,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Análisis y desarrollo de software',
                'description' => 'Diseño, desarrollo e implementación de soluciones de software.',
                'duration' => 27,
                'qualification_level_id' => 3,
                'area_id' => 1,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Gestión de redes de datos',
                'description' => 'Administración y optimización de redes de comunicación de datos.',
                'duration' => 27,
                'qualification_level_id' => 3,
                'area_id' => 1,
                'coordinator_id' => 17
            ],

            // ADMINISTRACIÓN Y GESTIÓN EMPRESARIAL (area_id = 2)
            [
                'name' => 'Gestión de la producción industrial',
                'description' => 'Planificación y control de procesos productivos industriales.',
                'duration' => 27,
                'qualification_level_id' => 3,
                'area_id' => 2,
                'coordinator_id' => 17
            ],
            [
                'name' => 'Coordinación en sistemas integrados de gestión',
                'description' => 'Implementación y coordinación de sistemas de gestión empresarial.',
                'duration' => 27,
                'qualification_level_id' => 3,
                'area_id' => 2,
                'coordinator_id' => 17
            ],

            // LOGÍSTICA Y TRANSPORTE (area_id = 9)
            [
                'name' => 'Gestión del mantenimiento de automotores',
                'description' => 'Administración de servicios de mantenimiento vehicular.',
                'duration' => 27,
                'qualification_level_id' => 3,
                'area_id' => 9,
                'coordinator_id' => 17
            ],

            // SEGURIDAD Y SALUD EN EL TRABAJO (area_id = 12)
            [
                'name' => 'Gestión de la seguridad y salud en el trabajo',
                'description' => 'Administración de programas de seguridad ocupacional y salud laboral.',
                'duration' => 27,
                'qualification_level_id' => 3,
                'area_id' => 12,
                'coordinator_id' => 17
            ],

            // DISEÑO Y COMUNICACIONES (area_id = 11)
            [
                'name' => 'Animación digital',
                'description' => 'Creación de animaciones 2D y 3D para medios digitales.',
                'duration' => 27,
                'qualification_level_id' => 3,
                'area_id' => 11,
                'coordinator_id' => 17
            ],
        ];

        foreach ($programs as $program) {
            TrainingProgram::create($program);
        }
    }
}