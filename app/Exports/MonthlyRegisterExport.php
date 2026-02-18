<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Exportación del registro mensual de asistencias en formato Excel.
 *
 * Genera un archivo .xlsx con la tabla de asistencias de una ficha,
 * incluyendo todos los aprendices, días, jornadas y marcas de asistencia.
 *
 * Implementa dos interfaces de Maatwebsite Excel:
 * - FromArray: provee los datos como array de filas
 * - WithEvents: permite manipular la hoja después de generarla (estilos, merges, etc.)
 *
 * Estructura del Excel generado:
 * - Filas 1-15: Espacio para encabezado institucional (logo, info ficha, etc.)
 * - Fila 16: Header de días (fecha + día de la semana)
 * - Fila 17: Header de jornadas (Mañana, Tarde, etc.)
 * - Filas 18+: Datos de aprendices con sus marcas de asistencia
 * - Después de datos: Leyenda de colores
 * - Al final: Tabla de aprendices inactivos (si existen)
 *
 * Características especiales:
 * - Merge de celdas para días con múltiples clases por jornada
 * - Columnas de ancho variable según cantidad de clases por slot
 * - Colores de celda según estado de asistencia
 * - Comentarios en celdas con detalle de clase y asistencia
 * - Columnas de totales CE (con excusa) y SE (sin excusa)
 * - Días sin clase marcados con fondo rojo y texto rotado
 * - Documentos como texto para preservar ceros a la izquierda
 *
 * Uso:
 * return Excel::download(new MonthlyRegisterExport($payload), 'registro.xlsx');
 *
 * Estructura esperada del $payload:
 * [
 *   'days'               => ['2024-01-15', '2024-01-16', ...],
 *   'slots'              => [['code' => 'M', 'label' => 'Mañana'], ...],
 *   'apprentices'        => [['document_number' => '...', 'full_name' => '...', 'marks_by_date_slot' => [...]], ...],
 *   'day_info_by_date'   => ['2024-01-15' => ['day_state' => 'no_class_day', 'reason' => [...]], ...],
 *   'classes_by_date_slot' => ['2024-01-15' => ['M' => [...clases...]], ...],
 *   'inactive_apprentices' => [['document_number' => '...', 'full_name' => '...'], ...]
 * ]
 */
class MonthlyRegisterExport implements FromArray, WithEvents
{
    /**
     * Comentarios de celdas a agregar después de generar el sheet.
     * Cada elemento: ['row' => int, 'col' => int, 'text' => string]
     *
     * @var array
     */
    private array $cellComments = [];

    /**
     * Estados de asistencia por celda para colorear.
     * Estructura: [row => [col => ['present', 'absent', ...]]]
     *
     * @var array
     */
    private array $cellMarks = [];

    /**
     * Rangos de columnas con días sin clase para estilos especiales.
     * Cada elemento: ['start' => int, 'end' => int, 'text' => string]
     *
     * @var array
     */
    private array $noClassDayRanges = [];

    /**
     * Rangos de columnas por día para hacer merge en header fila 1.
     * Cada elemento: ['start' => int, 'end' => int]
     *
     * @var array
     */
    private array $dayMergeRanges = [];

    /**
     * Índice de la última columna (para aplicar estilos hasta ahí).
     *
     * @var int
     */
    private int $lastColIndex = 1;

    /**
     * Número de la última fila con datos (para aplicar estilos hasta ahí).
     *
     * @var int
     */
    private int $lastRow = 1;

    /**
     * Crea una nueva instancia del exportador.
     *
     * @param array $payload Datos completos del registro mensual
     */
    public function __construct(private array $payload) {}

    /**
     * Genera el array de filas para el Excel.
     *
     * Es el método principal que construye toda la estructura de datos.
     * Se ejecuta primero, antes que registerEvents.
     *
     * Proceso:
     * 1. Calcula spans (anchos) por día/slot según cantidad de clases
     * 2. Construye filas de headers (días y jornadas)
     * 3. Construye filas de datos por aprendiz
     * 4. Guarda metadata para estilos (comentarios, colores, merges)
     *
     * @return array Array bidimensional de filas para el Excel
     */
    public function array(): array
    {
        // Extrae datos del payload con valores por defecto vacíos
        $days              = $this->payload['days'] ?? [];
        $slots             = $this->payload['slots'] ?? [];
        $apprentices       = $this->payload['apprentices'] ?? [];
        $dayInfoByDate     = $this->payload['day_info_by_date'] ?? [];
        $classesByDateSlot = $this->payload['classes_by_date_slot'] ?? [];

        // Extrae códigos de jornadas como strings (ej: ['M', 'T', 'N'])
        $slotCodes = array_map(fn($s) => (string)($s['code'] ?? ''), $slots);

        // Columnas fijas que van antes de los días dinámicos
        $fixed     = ['#', 'Documento', 'Aprendiz'];
        $fixedCols = count($fixed); // = 3

        /**
         * PASO 1: Calcular spans (anchos) por día y slot.
         *
         * Un día puede tener múltiples clases en una misma jornada.
         * El span define cuántas columnas ocupa cada jornada de cada día.
         *
         * Ejemplo:
         * - Lunes, Mañana: 2 clases → span = 2 → ocupa 2 columnas
         * - Lunes, Tarde: 1 clase → span = 1 → ocupa 1 columna
         * - Ancho total del Lunes: 3 columnas
         */
        $spans     = [];  // [fecha][slotCode] => span (int)
        $dayWidths = [];  // [fecha] => ancho total del día

        foreach ($days as $d) {
            $dayWidth = 0;

            foreach ($slotCodes as $sc) {
                // Obtiene las clases para este día/slot
                $classes = $classesByDateSlot[$d][$sc] ?? [];

                // Span mínimo de 1, máximo = cantidad de clases
                $span = max(1, is_array($classes) ? count($classes) : 1);

                $spans[$d][$sc] = $span;
                $dayWidth       += $span;
            }

            $dayWidths[$d] = $dayWidth;
        }

        /**
         * PASO 2: Construir headers de dos filas.
         *
         * Fila 1 (headerDay): fecha/día de la semana, mergeado por ancho del día
         * Fila 2 (headerSlot): etiqueta de jornada, repetida según span
         *
         * Columnas fijas:
         * - headerDay:  ['#', 'Documento', 'Aprendiz', 'Lun 01/01', '', '', ...]
         * - headerSlot: ['', '', '', 'Mañana', 'Mañana', 'Tarde', ...]
         */
        $headerDay  = $fixed;
        $headerSlot = array_fill(0, $fixedCols, ''); // Vacíos para las columnas fijas

        // Índice de columna actual (1-indexed para PhpSpreadsheet)
        $colIndex = $fixedCols + 1; // Empieza en columna D (4)

        foreach ($days as $d) {
            // Parsea la fecha con Carbon y formatea para el header
            $c   = Carbon::parse($d)->locale('es');
            $dow = ucfirst($c->isoFormat('ddd')); // Ej: "Lun"

            // Etiqueta del día: "Lun\n01/01"
            $label = $dow . "\n" . $c->format('d/m');

            // Rango de columnas que ocupa este día
            $dayStart = $colIndex;
            $dayEnd   = $colIndex + ($dayWidths[$d] - 1);

            // Guarda rango para hacer merge después en registerEvents
            $this->dayMergeRanges[] = ['start' => $dayStart, 'end' => $dayEnd];

            // Agrega etiqueta del día y celdas vacías para las columnas mergeadas
            $headerDay[] = $label;
            for ($i = $dayStart + 1; $i <= $dayEnd; $i++) {
                $headerDay[] = ''; // Celda vacía (parte del merge)
            }

            // Agrega etiquetas de jornada repetidas según span
            foreach ($slots as $s) {
                $sc   = (string)($s['code'] ?? '');
                $span = (int)($spans[$d][$sc] ?? 1);

                // Repite la etiqueta tantas veces como columnas tenga el slot
                for ($k = 0; $k < $span; $k++) {
                    $headerSlot[] = (string)($s['label'] ?? strtoupper($sc));
                }
            }

            // Guarda rango de días sin clase para estilos especiales
            if (($dayInfoByDate[$d]['day_state'] ?? '') === 'no_class_day') {
                $reason = $dayInfoByDate[$d]['reason']['name'] ?? 'Sin clase';
                $obs    = $dayInfoByDate[$d]['observations'] ?? null;

                // Combina razón y observaciones si existen
                $text = $obs ? ($reason . "\n" . $obs) : $reason;

                $this->noClassDayRanges[] = [
                    'start' => $dayStart,
                    'end'   => $dayEnd,
                    'text'  => $text,
                ];
            }

            // Avanza el índice al siguiente día
            $colIndex = $dayEnd + 1;
        }

        // Agrega columnas de totales al final del header
        $headerDay[]  = 'TOTAL\nHORAS'; // Mergeado sobre CE y SE
        $headerDay[]  = '';              // Celda vacía del merge
        $headerSlot[] = 'CE';           // Con Excusa
        $headerSlot[] = 'SE';           // Sin Excusa

        $rows = [];

        /**
         * PASO 3: Filas vacías para encabezado institucional.
         *
         * Las primeras 15 filas se dejan vacías para que el usuario
         * pueda agregar logo, información de la ficha, etc.
         */
        $headerOffset = 15;
        for ($i = 0; $i < $headerOffset; $i++) {
            $rows[] = array_fill(0, $fixedCols + array_sum($dayWidths) + 2, '');
        }

        // Agrega las dos filas de headers
        $rows[] = $headerDay;
        $rows[] = $headerSlot;

        /**
         * PASO 4: Filas de datos por aprendiz.
         *
         * Por cada aprendiz:
         * - Columnas fijas: número, documento, nombre
         * - Columnas dinámicas: marca de asistencia por día/slot/clase
         * - Columnas de totales: CE y SE
         */
        $dataStartRow = 3 + $headerOffset; // Fila 18 en Excel
        $r            = 0; // Contador de aprendiz

        foreach ($apprentices as $ap) {
            $r++;
            // Número de fila real en Excel para este aprendiz
            $excelRow = $dataStartRow + ($r - 1);

            // Columnas fijas
            $row = [
                $r,                                        // Número secuencial
                (string)($ap['document_number'] ?? ''),   // Documento como string (preserva ceros)
                $ap['full_name'] ?? '',                    // Nombre completo
            ];

            // Acumuladores de horas para totales
            $ce = 0; // Horas con excusa (excused_absence)
            $se = 0; // Horas sin excusa (absent + late)

            foreach ($days as $d) {

                /**
                 * Días sin clase: llenar todas las columnas del día con '0'.
                 * Se usa string '0' para que Excel lo muestre como cero visible.
                 */
                if (($dayInfoByDate[$d]['day_state'] ?? '') === 'no_class_day') {
                    for ($i = 0; $i < (int)$dayWidths[$d]; $i++) {
                        $row[] = '0';
                    }
                    continue;
                }

                // Procesa cada jornada del día
                foreach ($slots as $s) {
                    $sc   = (string)($s['code'] ?? '');
                    $span = (int)($spans[$d][$sc] ?? 1);

                    // Clases programadas para este día/slot
                    $classes = $classesByDateSlot[$d][$sc] ?? [];

                    // Marcas de asistencia del aprendiz para este día/slot
                    $marks = $ap['marks_by_date_slot'][$d][$sc] ?? [];

                    /**
                     * Indexa las marcas por real_class_id para acceso rápido.
                     * Permite asociar cada marca a su clase específica.
                     */
                    $marksByRealClass = [];
                    foreach ($marks as $m) {
                        $rcId = (int)($m['real_class_id'] ?? 0);
                        if ($rcId > 0) {
                            $marksByRealClass[$rcId][] = $m;
                        }
                    }

                    // Procesa cada columna-clase del slot (según span)
                    for ($k = 0; $k < $span; $k++) {
                        // Información de la clase en esta posición
                        $classInfo = is_array($classes) && isset($classes[$k]) ? $classes[$k] : null;

                        // ID de la clase real (0 si no hay clase)
                        $rcId = $classInfo ? (int)($classInfo['real_class_id'] ?? 0) : 0;

                        // Acumulador de horas ausentes para esta celda
                        $cellAbsent   = 0;

                        // Líneas del comentario de la celda
                        $commentLines = [];

                        // Estados de asistencia para colorear la celda
                        $statuses     = [];

                        /**
                         * Construye líneas del comentario con info de la clase.
                         */
                        if ($classInfo) {
                            $instructor = $classInfo['instructor']['full_name'] ?? 'Sin instructor';
                            $type       = $classInfo['class_type']['name'] ?? 'Sin tipo';
                            $start      = $classInfo['real_time']['start_hour'] ?? '';
                            $end        = $classInfo['real_time']['end_hour'] ?? '';

                            $commentLines[] = "CLASE:";
                            $commentLines[] = "{$type}";
                            $commentLines[] = "Instructor: {$instructor}";

                            if ($start && $end) {
                                $commentLines[] = "Horario: {$start}-{$end}";
                            }

                            if (!empty($classInfo['observations'])) {
                                $commentLines[] = "Obs clase: " . $classInfo['observations'];
                            }
                        }

                        /**
                         * Determina qué marcas corresponden a esta celda.
                         *
                         * Prioridad:
                         * 1. Marcas que coinciden con el real_class_id de la clase
                         * 2. Si no hay real_class_id, usa todas las marcas (fallback)
                         */
                        $marksForThisCell = [];

                        if ($rcId > 0 && isset($marksByRealClass[$rcId])) {
                            // Marcas específicas para esta clase
                            $marksForThisCell = $marksByRealClass[$rcId];
                        } elseif ($rcId === 0 && !empty($marks)) {
                            // Fallback: usa todas las marcas del slot
                            $marksForThisCell = $marks;
                        }

                        // Flag para distinguir celda sin datos vs datos con valor 0
                        $hasMarks = false;

                        if (!empty($marksForThisCell)) {
                            $hasMarks       = true;
                            $commentLines[] = "";
                            $commentLines[] = "ASISTENCIA:";

                            foreach ($marksForThisCell as $m) {
                                $status     = $m['status'] ?? 'unregistered';
                                $statusName = $m['status_name'] ?? ucfirst(str_replace('_', ' ', $status));
                                $ah         = (int)($m['absent_hours'] ?? 0); // Horas ausentes

                                // Guarda el estado para colorear la celda
                                $statuses[] = $status;

                                // Agrega línea del comentario con estado
                                $commentLines[] = "- Estado: {$statusName}" . ($ah ? " | Horas: {$ah}" : '');

                                // Hora de entrada si existe
                                if (!empty($m['entry_hour'])) {
                                    $commentLines[] = "  Entrada: " . $m['entry_hour'];
                                }

                                // Observaciones de la marca si existen
                                if (!empty($m['observations'])) {
                                    $commentLines[] = "  Obs: " . $m['observations'];
                                }

                                /**
                                 * Acumula horas ausentes según tipo de ausencia:
                                 * - excused_absence → CE (con excusa)
                                 * - absent / late → SE (sin excusa)
                                 */
                                if ($status === 'excused_absence') {
                                    $ce          += $ah;
                                    $cellAbsent  += $ah;
                                } elseif ($status === 'absent' || $status === 'late') {
                                    $se          += $ah;
                                    $cellAbsent  += $ah;
                                }
                            }
                        }

                        /**
                         * Valor visible de la celda:
                         * - Sin marcas → '' (vacío, celda sin datos)
                         * - Con marcas, 0 horas → '0' (string, asistió sin faltas)
                         * - Con marcas, > 0 horas → número de horas ausentes
                         */
                        if (!$hasMarks) {
                            $row[] = '';
                        } else {
                            $row[] = $cellAbsent === 0 ? '0' : (int)$cellAbsent;
                        }

                        // Columna real en Excel de esta celda (para comentarios y colores)
                        $colInExcel = count($row);

                        // Guarda comentario si tiene contenido relevante
                        if (!empty(array_filter($commentLines, fn($x) => $x !== ''))) {
                            $this->cellComments[] = [
                                'row'  => $excelRow,
                                'col'  => $colInExcel,
                                'text' => implode("\n", $commentLines),
                            ];
                        }

                        // Guarda estados para colorear la celda
                        if (!empty($statuses)) {
                            $this->cellMarks[$excelRow][$colInExcel] = $statuses;
                        }
                    }
                }
            }

            // Agrega columnas de totales al final de la fila
            $row[] = $ce === 0 ? '0' : $ce; // Total CE
            $row[] = $se === 0 ? '0' : $se; // Total SE

            $rows[] = $row;
        }

        // Guarda índices finales para usar en registerEvents
        $this->lastRow      = $dataStartRow + max(0, count($apprentices) - 1);
        $this->lastColIndex = count($rows[0]); // Ancho total del Excel

        return $rows;
    }

    /**
     * Registra los eventos de Maatwebsite Excel.
     *
     * AfterSheet se ejecuta después de que los datos fueron escritos,
     * permitiendo aplicar estilos, merges, comentarios y formatos.
     *
     * @return array Array de eventos con sus callbacks
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Obtiene la hoja de cálculo nativa de PhpSpreadsheet
                $sheet = $event->sheet->getDelegate();

                // Filas de referencia
                $headerOffset = 15;
                $headerRow1   = 1 + $headerOffset;  // Fila 16: días
                $headerRow2   = 2 + $headerOffset;  // Fila 17: jornadas
                $dataStartRow = 3 + $headerOffset;  // Fila 18: primer aprendiz

                // Letra de la última columna (ej: "Z", "AA")
                $lastColLetter = Coordinate::stringFromColumnIndex($this->lastColIndex);

                /**
                 * MERGE: Columnas fijas en header fila 1.
                 *
                 * Las columnas #, Documento y Aprendiz deben ocupar
                 * las dos filas del header (merge vertical).
                 */
                $sheet->mergeCells("A{$headerRow1}:A{$headerRow2}"); // #
                $sheet->mergeCells("B{$headerRow1}:B{$headerRow2}"); // Documento
                $sheet->mergeCells("C{$headerRow1}:C{$headerRow2}"); // Aprendiz

                /**
                 * MERGE: Días en fila 1.
                 *
                 * Cada día ocupa N columnas (su dayWidth).
                 * Se hace merge horizontal para que el nombre del día
                 * aparezca centrado sobre todas sus jornadas.
                 */
                foreach ($this->dayMergeRanges as $rng) {
                    $start = Coordinate::stringFromColumnIndex($rng['start']);
                    $end   = Coordinate::stringFromColumnIndex($rng['end']);
                    $sheet->mergeCells("{$start}{$headerRow1}:{$end}{$headerRow1}");
                }

                /**
                 * MERGE: "TOTAL HORAS" sobre CE y SE.
                 *
                 * Las últimas dos columnas (CE y SE) tienen un header
                 * común "TOTAL HORAS" mergeado horizontalmente.
                 */
                $ceStart = Coordinate::stringFromColumnIndex($this->lastColIndex - 1);
                $ceEnd   = Coordinate::stringFromColumnIndex($this->lastColIndex);
                $sheet->mergeCells("{$ceStart}{$headerRow1}:{$ceEnd}{$headerRow1}");

                /**
                 * ESTILOS: Headers generales.
                 *
                 * Aplica fuente bold, alineación centrada y fondo gris claro
                 * a todas las celdas de las dos filas de header.
                 */
                $sheet->getStyle("A{$headerRow1}:{$lastColLetter}{$headerRow2}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 10],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'wrapText'   => true, // Permite saltos de línea en celdas
                    ],
                ]);

                // Altura de filas de header
                $sheet->getRowDimension($headerRow1)->setRowHeight(28); // Más alta para el día
                $sheet->getRowDimension($headerRow2)->setRowHeight(18);

                // Fondo gris claro para headers
                $sheet->getStyle("A{$headerRow1}:{$lastColLetter}{$headerRow2}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F3F4F6');

                /**
                 * ESTILOS: Días sin clase.
                 *
                 * Para cada rango de columnas marcado como día sin clase:
                 * - Merge vertical de todas las filas de datos
                 * - Fondo rojo pálido
                 * - Texto rojo rotado 90° con el motivo del día sin clase
                 */
                foreach ($this->noClassDayRanges as $ncd) {
                    $startCol = Coordinate::stringFromColumnIndex($ncd['start']);
                    $endCol   = Coordinate::stringFromColumnIndex($ncd['end']);

                    // Merge de toda la columna verticalmente (desde datos hasta última fila)
                    $range = "{$startCol}{$dataStartRow}:{$endCol}{$this->lastRow}";
                    $sheet->mergeCells($range);

                    // Establece el texto del motivo en la primera celda del rango
                    $sheet->setCellValue("{$startCol}{$dataStartRow}", $ncd['text']);

                    // Estilos del día sin clase: fondo rojo, texto rojo rotado 90°
                    $sheet->getStyle($range)->applyFromArray([
                        'fill'      => [
                            'fillType' => Fill::FILL_SOLID,
                            'color'    => ['rgb' => 'FFE5E5'], // Rojo muy pálido
                        ],
                        'font'      => [
                            'color' => ['rgb' => 'DC2626'], // Rojo
                            'bold'  => true,
                            'size'  => 11,
                        ],
                        'alignment' => [
                            'horizontal'   => Alignment::HORIZONTAL_CENTER,
                            'vertical'     => Alignment::VERTICAL_CENTER,
                            'textRotation' => 90,  // Texto vertical
                            'wrapText'     => true,
                        ],
                        'borders'   => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color'       => ['rgb' => '000000'],
                            ],
                        ],
                    ]);
                }

                /**
                 * COMENTARIOS: Info detallada en cada celda de asistencia.
                 *
                 * Cada celda puede tener un comentario con:
                 * - Información de la clase (instructor, tipo, horario)
                 * - Estado de asistencia del aprendiz
                 * - Horas ausentes, hora de entrada, observaciones
                 */
                foreach ($this->cellComments as $cmt) {
                    // Convierte índice de columna a letra (ej: 4 → "D")
                    $cell    = Coordinate::stringFromColumnIndex((int)$cmt['col']) . (int)$cmt['row'];
                    $comment = $sheet->getComment($cell);

                    $comment->setAuthor('Asistencia');                           // Autor del comentario
                    $comment->getText()->createTextRun((string)$cmt['text']);    // Texto del comentario
                    $comment->setWidth('320pt');                                  // Ancho del popup
                    $comment->setHeight('180pt');                                 // Alto del popup
                }

                /**
                 * COLORES: Por estado de asistencia.
                 *
                 * Colores de fondo (pasteles suaves):
                 * - present:         verde pastel
                 * - absent:          rojo pálido
                 * - late:            naranja suave
                 * - excused_absence: morado pastel
                 * - early_exit:      azul claro
                 * - unregistered:    gris claro
                 */
                $stateColors = [
                    'present'         => 'b7f3cd', // Verde pastel
                    'absent'          => 'FFE5E5', // Rojo pálido
                    'late'            => 'eeb96a', // Naranja suave
                    'excused_absence' => 'c4b4eb', // Morado pastel
                    'early_exit'      => 'a2dbf1', // Azul claro
                    'unregistered'    => 'F9FAFB', // Gris claro
                ];

                /**
                 * Colores del texto (más oscuros que el fondo para contraste).
                 */
                $stateLetterColors = [
                    'present'         => '3b664b', // Verde oscuro
                    'absent'          => 'DC2626', // Rojo
                    'late'            => '503608', // Marrón oscuro
                    'excused_absence' => '452692', // Morado oscuro
                    'early_exit'      => '3b48fa', // Azul
                    'unregistered'    => '000000', // Negro
                ];

                /**
                 * Prioridad de estados cuando una celda tiene múltiples.
                 *
                 * Si hay varios estados en una celda (ej: tarde y presente),
                 * se aplica el color del estado más grave (menor número = más prioritario).
                 */
                $priority = [
                    'absent'          => 1, // Más grave
                    'excused_absence' => 2,
                    'late'            => 3,
                    'early_exit'      => 4,
                    'present'         => 5,
                    'unregistered'    => 6, // Menos grave
                ];

                // Aplica colores a cada celda con marcas
                for ($row = $dataStartRow; $row <= $this->lastRow; $row++) {
                    if (!isset($this->cellMarks[$row])) continue;

                    foreach ($this->cellMarks[$row] as $col => $statuses) {
                        if (empty($statuses)) continue;

                        // Determina el estado con mayor prioridad (más grave)
                        $chosen      = null;
                        $chosenPrio  = 999;

                        foreach ($statuses as $st) {
                            if (!isset($priority[$st])) continue;

                            if ($priority[$st] < $chosenPrio) {
                                $chosenPrio = $priority[$st];
                                $chosen     = $st;
                            }
                        }

                        // Si no hay estado válido o no tiene color, salta
                        if (!$chosen || !isset($stateColors[$chosen])) continue;

                        // Convierte índice de columna a referencia de celda
                        $colLetter = Coordinate::stringFromColumnIndex($col);
                        $cellRef   = "{$colLetter}{$row}";

                        // Aplica fondo, color de texto y borde
                        $sheet->getStyle($cellRef)->applyFromArray([
                            'fill'    => [
                                'fillType' => Fill::FILL_SOLID,
                                'color'    => ['rgb' => $stateColors[$chosen]],
                            ],
                            'font'    => [
                                'color' => ['rgb' => $stateLetterColors[$chosen]],
                            ],
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color'       => ['rgb' => '000000'],
                                ],
                            ],
                        ]);
                    }
                }

                /**
                 * FORMATO DE NÚMEROS: Columnas de datos.
                 *
                 * Aplica formato de entero ('0') a todas las columnas dinámicas.
                 * Centra el contenido vertical y horizontalmente.
                 */
                $firstSlotColIndex = 4;                        // D = primera columna dinámica
                $seColIndex        = $this->lastColIndex;      // Última columna = SE
                $lastDataRow       = $this->lastRow;

                $firstCol  = Coordinate::stringFromColumnIndex($firstSlotColIndex);
                $lastCol   = Coordinate::stringFromColumnIndex($seColIndex);
                $dataRange = "{$firstCol}{$dataStartRow}:{$lastCol}{$lastDataRow}";

                // Formato número entero (sin decimales)
                $sheet->getStyle($dataRange)->getNumberFormat()
                    ->setFormatCode('0');

                // Centrado de contenido
                $sheet->getStyle($dataRange)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                /**
                 * FORMATO TEXTO: Columna de documentos.
                 *
                 * Fuerza formato de texto en la columna B (Documento)
                 * para preservar ceros a la izquierda en documentos.
                 * Ej: "012345678" no se convierte a 12345678
                 */
                $sheet->getStyle("B{$dataStartRow}:B{$this->lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode('@'); // @ = formato texto en Excel

                /**
                 * LEYENDA DE COLORES.
                 *
                 * Se agrega 3 filas después del último aprendiz.
                 * Muestra cada estado con su color correspondiente.
                 */
                $legendStartRow = $this->lastRow + 3;

                // Título de la leyenda
                $sheet->setCellValue("A{$legendStartRow}", "LEYENDA DE ESTADOS");
                $sheet->getStyle("A{$legendStartRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                ]);

                // Items de la leyenda con colores y nombres
                $legendItems = [
                    ['color' => $stateColors['present'],         'textColor' => $stateLetterColors['present'],         'name' => 'Asistencia'],
                    ['color' => $stateColors['absent'],          'textColor' => $stateLetterColors['absent'],          'name' => 'Inasistencia'],
                    ['color' => $stateColors['late'],            'textColor' => $stateLetterColors['late'],            'name' => 'Tardanza'],
                    ['color' => $stateColors['excused_absence'], 'textColor' => $stateLetterColors['excused_absence'], 'name' => 'Ausencia Justificada'],
                    ['color' => $stateColors['early_exit'],      'textColor' => $stateLetterColors['early_exit'],      'name' => 'Salida Anticipada'],
                    ['color' => $stateColors['unregistered'],    'textColor' => $stateLetterColors['unregistered'],    'name' => 'Sin Registrar'],
                ];

                $legendRow = $legendStartRow + 1;

                foreach ($legendItems as $item) {
                    // Escribe nombre del estado en columna A
                    $sheet->setCellValue("A{$legendRow}", $item['name']);

                    // Aplica color de fondo, texto y borde al item de leyenda
                    $sheet->getStyle("A{$legendRow}:B{$legendRow}")->applyFromArray([
                        'fill'      => [
                            'fillType' => Fill::FILL_SOLID,
                            'color'    => ['rgb' => $item['color']],
                        ],
                        'font'      => [
                            'color' => ['rgb' => $item['textColor']],
                            'bold'  => true,
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER,
                        ],
                        'borders'   => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color'       => ['rgb' => '000000'],
                            ],
                        ],
                    ]);

                    // Merge columnas A y B para el item de leyenda
                    $sheet->mergeCells("A{$legendRow}:B{$legendRow}");

                    $legendRow++;
                }

                /**
                 * TABLA DE APRENDICES INACTIVOS.
                 *
                 * Si existen aprendices inactivos en el payload,
                 * se agrega una tabla separada después de la leyenda.
                 * Aprendices inactivos: retirados, trasladados, etc.
                 */
                $inactiveApprentices = $this->payload['inactive_apprentices'] ?? [];

                if (!empty($inactiveApprentices)) {
                    // Empieza 2 filas después de la leyenda
                    $inactiveStartRow = $legendRow + 2;

                    // Título de la sección
                    $sheet->setCellValue("A{$inactiveStartRow}", "APRENDICES INACTIVOS");
                    $sheet->mergeCells("A{$inactiveStartRow}:C{$inactiveStartRow}");
                    $sheet->getStyle("A{$inactiveStartRow}")->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'DC2626']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);

                    // Fila de headers de la tabla
                    $headerInactiveRow = $inactiveStartRow + 1;
                    $sheet->setCellValue("A{$headerInactiveRow}", "#");
                    $sheet->setCellValue("B{$headerInactiveRow}", "Documento");
                    $sheet->setCellValue("C{$headerInactiveRow}", "Nombre Completo");

                    // Estilos del header de la tabla de inactivos
                    $sheet->getStyle("A{$headerInactiveRow}:C{$headerInactiveRow}")->applyFromArray([
                        'font'      => ['bold' => true],
                        'fill'      => [
                            'fillType' => Fill::FILL_SOLID,
                            'color'    => ['rgb' => 'FFE5E5'], // Rojo pálido
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER,
                        ],
                        'borders'   => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color'       => ['rgb' => '000000'],
                            ],
                        ],
                    ]);

                    // Filas de datos de aprendices inactivos
                    $inactiveDataRow = $headerInactiveRow + 1;
                    $counter         = 1;

                    foreach ($inactiveApprentices as $inactive) {
                        $sheet->setCellValue("A{$inactiveDataRow}", $counter);

                        // Fuerza tipo string para preservar ceros del documento
                        $sheet->setCellValueExplicit(
                            "B{$inactiveDataRow}",
                            (string)($inactive['document_number'] ?? ''),
                            DataType::TYPE_STRING
                        );

                        $sheet->setCellValue("C{$inactiveDataRow}", $inactive['full_name'] ?? '');

                        // Estilos de la fila
                        $sheet->getStyle("A{$inactiveDataRow}:C{$inactiveDataRow}")->applyFromArray([
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical'   => Alignment::VERTICAL_CENTER,
                            ],
                            'borders'   => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color'       => ['rgb' => '000000'],
                                ],
                            ],
                        ]);

                        $inactiveDataRow++;
                        $counter++;
                    }

                    // Anchos de columna para la sección de inactivos
                    $sheet->getColumnDimension('A')->setWidth(5);  // #
                    $sheet->getColumnDimension('B')->setWidth(15); // Documento
                    $sheet->getColumnDimension('C')->setWidth(40); // Nombre
                }

                /**
                 * BORDES FINALES: Tabla principal.
                 *
                 * Se aplican al final para sobreescribir cualquier borde
                 * que haya sido eliminado por estilos anteriores.
                 * Aplica borde fino negro a toda la tabla de asistencias.
                 */
                $sheet->getStyle("A{$headerRow1}:{$lastColLetter}{$this->lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => '000000'],
                        ],
                    ],
                ]);
            },
        ];
    }
}