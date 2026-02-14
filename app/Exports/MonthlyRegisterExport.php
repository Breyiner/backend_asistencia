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

class MonthlyRegisterExport implements FromArray, WithEvents
{
    private array $cellComments = [];
    private array $cellMarks = [];
    private array $noClassDayRanges = [];
    private array $dayMergeRanges = [];    private int $lastColIndex = 1;
    private int $lastRow = 1;

    public function __construct(private array $payload) {}

    public function array(): array
    {
        $days = $this->payload['days'] ?? [];
        $slots = $this->payload['slots'] ?? [];
        $apprentices = $this->payload['apprentices'] ?? [];
        $dayInfoByDate = $this->payload['day_info_by_date'] ?? [];
        $classesByDateSlot = $this->payload['classes_by_date_slot'] ?? [];

        $slotCodes = array_map(fn($s) => (string)($s['code'] ?? ''), $slots);

        $fixed = ['#', 'Documento', 'Aprendiz'];
        $fixedCols = count($fixed);

        /**
         * 1) spans por día/slot (para manejar múltiples clases por jornada)
         */
        $spans = [];
        $dayWidths = [];

        foreach ($days as $d) {
            $dayWidth = 0;

            foreach ($slotCodes as $sc) {
                $classes = $classesByDateSlot[$d][$sc] ?? [];
                $span = max(1, is_array($classes) ? count($classes) : 1);

                $spans[$d][$sc] = $span;
                $dayWidth += $span;
            }

            $dayWidths[$d] = $dayWidth;
        }

        /**
         * 2) Headers (2 filas)
         *    Fila 1: día+fecha mergeado por ancho del día
         *    Fila 2: jornada repetida según span
         */
        $headerDay = $fixed;
        $headerSlot = array_fill(0, $fixedCols, '');

        $colIndex = $fixedCols + 1; // primera dinámica (1-indexed)
        foreach ($days as $d) {
            $c = Carbon::parse($d)->locale('es');
            $dow = ucfirst($c->isoFormat('ddd'));
            $label = $dow . "\n" . $c->format('d/m');

            $dayStart = $colIndex;
            $dayEnd = $colIndex + ($dayWidths[$d] - 1);

            $this->dayMergeRanges[] = ['start' => $dayStart, 'end' => $dayEnd];

            $headerDay[] = $label;
            for ($i = $dayStart + 1; $i <= $dayEnd; $i++) {
                $headerDay[] = '';
            }

            // headerSlot: jornada * span
            foreach ($slots as $s) {
                $sc = (string)($s['code'] ?? '');
                $span = (int)($spans[$d][$sc] ?? 1);

                for ($k = 0; $k < $span; $k++) {
                    $headerSlot[] = (string)($s['label'] ?? strtoupper($sc));
                }
            }

            // día sin clase → rango completo
            if (($dayInfoByDate[$d]['day_state'] ?? '') === 'no_class_day') {
                $reason = $dayInfoByDate[$d]['reason']['name'] ?? 'Sin clase';
                $obs = $dayInfoByDate[$d]['observations'] ?? null;
                $text = $obs ? ($reason . "\n" . $obs) : $reason;

                $this->noClassDayRanges[] = ['start' => $dayStart, 'end' => $dayEnd, 'text' => $text];
            }

            $colIndex = $dayEnd + 1;
        }

        // Header final: TOTAL HORAS (mergeado sobre CE/SE)
        $headerDay[] = 'TOTAL\nHORAS';
        $headerDay[] = '';

        $headerSlot[] = 'CE';
        $headerSlot[] = 'SE';

        $rows = [];
        
        // Agregar 15 filas vacías para el encabezado
        $headerOffset = 15;
        for ($i = 0; $i < $headerOffset; $i++) {
            $rows[] = array_fill(0, $fixedCols + array_sum($dayWidths) + 2, '');
        }
        
        $rows[] = $headerDay;
        $rows[] = $headerSlot;

        /**
         * 3) Data rows
         */
        $dataStartRow = 3 + $headerOffset;
        $r = 0;

        foreach ($apprentices as $ap) {
            $r++;
            $excelRow = $dataStartRow + ($r - 1);

            $row = [
                $r,
                (string)($ap['document_number'] ?? ''), // String para preservar ceros
                $ap['full_name'] ?? '',
            ];

            $ce = 0;
            $se = 0;

            foreach ($days as $d) {
                // Día sin clase: llenar con 0 en todo el ancho del día
                if (($dayInfoByDate[$d]['day_state'] ?? '') === 'no_class_day') {
                    for ($i = 0; $i < (int)$dayWidths[$d]; $i++) {
                        $row[] = '0'; // String para que se muestre el cero
                    }
                    continue;
                }

                foreach ($slots as $s) {
                    $sc = (string)($s['code'] ?? '');
                    $span = (int)($spans[$d][$sc] ?? 1);

                    $classes = $classesByDateSlot[$d][$sc] ?? [];
                    $marks = $ap['marks_by_date_slot'][$d][$sc] ?? [];

                    // Indexar marks por real_class_id
                    $marksByRealClass = [];
                    foreach ($marks as $m) {
                        $rcId = (int)($m['real_class_id'] ?? 0);
                        if ($rcId > 0) {
                            $marksByRealClass[$rcId][] = $m;
                        }
                    }

                    // Para cada columna-clase del slot
                    for ($k = 0; $k < $span; $k++) {
                        $classInfo = is_array($classes) && isset($classes[$k]) ? $classes[$k] : null;
                        $rcId = $classInfo ? (int)($classInfo['real_class_id'] ?? 0) : 0;

                        $cellAbsent = 0;
                        $commentLines = [];
                        $statuses = [];

                        // Info de clase
                        if ($classInfo) {
                            $instructor = $classInfo['instructor']['full_name'] ?? 'Sin instructor';
                            $type = $classInfo['class_type']['name'] ?? 'Sin tipo';
                            $start = $classInfo['real_time']['start_hour'] ?? '';
                            $end = $classInfo['real_time']['end_hour'] ?? '';

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

                        // Marcas para esta celda
                        $marksForThisCell = [];
                        if ($rcId > 0 && isset($marksByRealClass[$rcId])) {
                            $marksForThisCell = $marksByRealClass[$rcId];
                        } elseif ($rcId === 0 && !empty($marks)) {
                            // fallback
                            $marksForThisCell = $marks;
                        }

                        $hasMarks = false; // Flag para saber si hay registros
                        
                        if (!empty($marksForThisCell)) {
                            $hasMarks = true;
                            $commentLines[] = "";
                            $commentLines[] = "ASISTENCIA:";
                            foreach ($marksForThisCell as $m) {
                                $status = $m['status'] ?? 'unregistered';
                                $statusName = $m['status_name'] ?? ucfirst(str_replace('_', ' ', $status));
                                $ah = (int)($m['absent_hours'] ?? 0);

                                $statuses[] = $status;

                                $commentLines[] = "- Estado: {$statusName}" . ($ah ? " | Horas: {$ah}" : '');

                                if (!empty($m['entry_hour'])) {
                                    $commentLines[] = "  Entrada: " . $m['entry_hour'];
                                }
                                if (!empty($m['observations'])) {
                                    $commentLines[] = "  Obs: " . $m['observations'];
                                }

                                // CE/SE + valor visible
                                if ($status === 'excused_absence') {
                                    $ce += $ah;
                                    $cellAbsent += $ah;
                                } elseif ($status === 'absent' || $status === 'late') {
                                    $se += $ah;
                                    $cellAbsent += $ah;
                                }
                            }
                        }

                        // Valor visible: 
                        // - Si NO hay marcas → vacío
                        // - Si hay marcas y es 0 → '0' (string)
                        // - Si hay marcas y es > 0 → número
                        if (!$hasMarks) {
                            $row[] = '';
                        } else {
                            $row[] = $cellAbsent === 0 ? '0' : (int)$cellAbsent;
                        }
                        $colInExcel = count($row);

                        // Guardar comentario
                        if (!empty(array_filter($commentLines, fn($x) => $x !== ''))) {
                            $this->cellComments[] = [
                                'row' => $excelRow,
                                'col' => $colInExcel,
                                'text' => implode("\n", $commentLines),
                            ];
                        }

                        // Guardar estados para color
                        if (!empty($statuses)) {
                            $this->cellMarks[$excelRow][$colInExcel] = $statuses;
                        }
                    }
                }
            }

            // CE, SE
            $row[] = $ce === 0 ? '0' : $ce;
            $row[] = $se === 0 ? '0' : $se;

            $rows[] = $row;
        }

        // lastRow/lastCol para estilos
        $this->lastRow = $dataStartRow + max(0, count($apprentices) - 1);
        $this->lastColIndex = count($rows[0]);

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $headerOffset = 15;
                $headerRow1 = 1 + $headerOffset;  // Fila 16
                $headerRow2 = 2 + $headerOffset;  // Fila 17
                $dataStartRow = 3 + $headerOffset; // Fila 18

                $lastColLetter = Coordinate::stringFromColumnIndex($this->lastColIndex);

                // Merge columnas fijas (#, Documento, Aprendiz) verticalmente
                $sheet->mergeCells("A{$headerRow1}:A{$headerRow2}"); // #
                $sheet->mergeCells("B{$headerRow1}:B{$headerRow2}"); // Documento
                $sheet->mergeCells("C{$headerRow1}:C{$headerRow2}"); // Aprendiz

                // Merge día/fecha (fila 1)
                foreach ($this->dayMergeRanges as $rng) {
                    $start = Coordinate::stringFromColumnIndex($rng['start']);
                    $end = Coordinate::stringFromColumnIndex($rng['end']);
                    $sheet->mergeCells("{$start}{$headerRow1}:{$end}{$headerRow1}");
                }

                // Merge "TOTAL HORAS" sobre CE/SE
                $ceStart = Coordinate::stringFromColumnIndex($this->lastColIndex - 1);
                $ceEnd = Coordinate::stringFromColumnIndex($this->lastColIndex);
                $sheet->mergeCells("{$ceStart}{$headerRow1}:{$ceEnd}{$headerRow1}");

                // Headers
                $sheet->getStyle("A{$headerRow1}:{$lastColLetter}{$headerRow2}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $sheet->getRowDimension($headerRow1)->setRowHeight(28);
                $sheet->getRowDimension($headerRow2)->setRowHeight(18);

                $sheet->getStyle("A{$headerRow1}:{$lastColLetter}{$headerRow2}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F3F4F6');

                // Días sin clase: merge vertical completo + texto rotado + rojo
                foreach ($this->noClassDayRanges as $ncd) {
                    $startCol = Coordinate::stringFromColumnIndex($ncd['start']);
                    $endCol = Coordinate::stringFromColumnIndex($ncd['end']);

                    // Merge toda la columna verticalmente (de startCol a endCol, todas las filas)
                    $range = "{$startCol}{$dataStartRow}:{$endCol}{$this->lastRow}";
                    $sheet->mergeCells($range);

                    // Establecer el texto en la primera celda
                    $sheet->setCellValue("{$startCol}{$dataStartRow}", $ncd['text']);

                    // Aplicar estilos: fondo rojo pálido, texto rojo, rotado 90 grados
                    $sheet->getStyle($range)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'color' => ['rgb' => 'FFE5E5'], // Rojo muy pálido para fondo
                        ],
                        'font' => [
                            'color' => ['rgb' => 'DC2626'], // Rojo para el texto
                            'bold' => true,
                            'size' => 11,
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'textRotation' => 90, // Texto vertical
                            'wrapText' => true,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                    ]);
                }

                // Comentarios
                foreach ($this->cellComments as $cmt) {
                    $cell = Coordinate::stringFromColumnIndex((int)$cmt['col']) . (int)$cmt['row'];
                    $comment = $sheet->getComment($cell);
                    $comment->setAuthor('Asistencia');
                    $comment->getText()->createTextRun((string)$cmt['text']);
                    $comment->setWidth('320pt');
                    $comment->setHeight('180pt');
                }

                // Colores por estado
                $stateColors = [
                    'present'          => 'b7f3cd', // verde pastel
                    'absent'           => 'FFE5E5', // rojo pálido
                    'late'             => 'eeb96a', // naranja suave
                    'excused_absence'  => 'c4b4eb', // morado pastel
                    'early_exit'       => 'a2dbf1', // azul claro
                    'unregistered'     => 'F9FAFB', // gris claro
                ];

                $stateLetterColors = [
                    'present'          => '3b664b', 
                    'absent'           => 'DC2626', 
                    'late'             => '503608', 
                    'excused_absence'  => '452692', 
                    'early_exit'       => '3b48fa', 
                    'unregistered'     => '000000', 
                ];

                $priority = [
                    'absent'           => 1,
                    'excused_absence'  => 2,
                    'late'             => 3,
                    'early_exit'       => 4,
                    'present'          => 5,
                    'unregistered'     => 6,
                ];

                for ($row = $dataStartRow; $row <= $this->lastRow; $row++) {
                    if (!isset($this->cellMarks[$row])) continue;

                    foreach ($this->cellMarks[$row] as $col => $statuses) {
                        if (empty($statuses)) continue;

                        $chosen = null;
                        $chosenPrio = 999;

                        foreach ($statuses as $st) {
                            if (!isset($priority[$st])) continue;
                            if ($priority[$st] < $chosenPrio) {
                                $chosenPrio = $priority[$st];
                                $chosen = $st;
                            }
                        }

                        if (!$chosen || !isset($stateColors[$chosen])) continue;

                        $colLetter = Coordinate::stringFromColumnIndex($col);
                        $cellRef = "{$colLetter}{$row}";

                        $sheet->getStyle($cellRef)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'color' => ['rgb' => $stateColors[$chosen]],
                            ],
                            'font' => [
                                'color' => ['rgb' => $stateLetterColors[$chosen]],
                            ],
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => ['rgb' => '000000'],
                                ],
                            ],
                        ]);
                    }
                }

                // *** FORMATO DE NÚMEROS ***
                $firstSlotColIndex = 4;                          // D (primera columna de slots)
                $seColIndex = $this->lastColIndex;               // SE
                $lastDataRow = $this->lastRow;

                // Aplicar formato de número a todas las columnas de datos
                $firstCol = Coordinate::stringFromColumnIndex($firstSlotColIndex);
                $lastCol = Coordinate::stringFromColumnIndex($seColIndex);
                $dataRange = "{$firstCol}{$dataStartRow}:{$lastCol}{$lastDataRow}";

                $sheet->getStyle($dataRange)->getNumberFormat()
                    ->setFormatCode('0');  // Formato número entero sin decimales

                // Centrar contenido en columnas de datos
                $sheet->getStyle($dataRange)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // Formatear columna B (Documento) como texto para preservar ceros
                $sheet->getStyle("B{$dataStartRow}:B{$this->lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode('@'); // @ = formato de texto

                // *** LEYENDA DE COLORES ***
                $legendStartRow = $this->lastRow + 3; // 2 filas de separación
                
                // Título de la leyenda
                $sheet->setCellValue("A{$legendStartRow}", "LEYENDA DE ESTADOS");
                $sheet->getStyle("A{$legendStartRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                ]);
                
                // Usar los mismos colores que están en las variables de estado
                $legendItems = [
                    ['color' => $stateColors['present'], 'textColor' => $stateLetterColors['present'], 'name' => 'Asistencia'],
                    ['color' => $stateColors['absent'], 'textColor' => $stateLetterColors['absent'], 'name' => 'Inasistencia'],
                    ['color' => $stateColors['late'], 'textColor' => $stateLetterColors['late'], 'name' => 'Tardanza'],
                    ['color' => $stateColors['excused_absence'], 'textColor' => $stateLetterColors['excused_absence'], 'name' => 'Ausencia Justificada'],
                    ['color' => $stateColors['early_exit'], 'textColor' => $stateLetterColors['early_exit'], 'name' => 'Salida Anticipada'],
                    ['color' => $stateColors['unregistered'], 'textColor' => $stateLetterColors['unregistered'], 'name' => 'Sin Registrar'],
                ];
                
                $legendRow = $legendStartRow + 1;
                foreach ($legendItems as $item) {
                    // Columna A: Color
                    $sheet->setCellValue("A{$legendRow}", $item['name']);
                    $sheet->getStyle("A{$legendRow}:B{$legendRow}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'color' => ['rgb' => $item['color']],
                        ],
                        'font' => [
                            'color' => ['rgb' => $item['textColor']],
                            'bold' => true,
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                    ]);
                    
                    // Merge A y B para la leyenda
                    $sheet->mergeCells("A{$legendRow}:B{$legendRow}");
                    
                    $legendRow++;
                }

                // *** TABLA DE APRENDICES INACTIVOS ***
                $inactiveApprentices = $this->payload['inactive_apprentices'] ?? [];
                
                if (!empty($inactiveApprentices)) {
                    $inactiveStartRow = $legendRow + 2; // 1 fila de separación después de la leyenda
                    
                    // Título
                    $sheet->setCellValue("A{$inactiveStartRow}", "APRENDICES INACTIVOS");
                    $sheet->mergeCells("A{$inactiveStartRow}:C{$inactiveStartRow}");
                    $sheet->getStyle("A{$inactiveStartRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'DC2626']],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                        ],
                    ]);
                    
                    // Headers de la tabla
                    $headerInactiveRow = $inactiveStartRow + 1;
                    $sheet->setCellValue("A{$headerInactiveRow}", "#");
                    $sheet->setCellValue("B{$headerInactiveRow}", "Documento");
                    $sheet->setCellValue("C{$headerInactiveRow}", "Nombre Completo");
                    
                    $sheet->getStyle("A{$headerInactiveRow}:C{$headerInactiveRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'color' => ['rgb' => 'FFE5E5'], // Rojo pálido
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                    ]);
                    
                    // Datos de aprendices inactivos
                    $inactiveDataRow = $headerInactiveRow + 1;
                    $counter = 1;
                    foreach ($inactiveApprentices as $inactive) {
                        $sheet->setCellValue("A{$inactiveDataRow}", $counter);
                        $sheet->setCellValueExplicit(
                            "B{$inactiveDataRow}",
                            (string)($inactive['document_number'] ?? ''),
                            DataType::TYPE_STRING
                        );
                        $sheet->setCellValue("C{$inactiveDataRow}", $inactive['full_name'] ?? '');
                        
                        $sheet->getStyle("A{$inactiveDataRow}:C{$inactiveDataRow}")->applyFromArray([
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical' => Alignment::VERTICAL_CENTER,
                            ],
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => ['rgb' => '000000'],
                                ],
                            ],
                        ]);
                        
                        $inactiveDataRow++;
                        $counter++;
                    }
                    
                    // Ajustar anchos de columna para la tabla de inactivos
                    $sheet->getColumnDimension('A')->setWidth(5);
                    $sheet->getColumnDimension('B')->setWidth(15);
                    $sheet->getColumnDimension('C')->setWidth(40);
                }

                // *** APLICAR BORDES NEGROS AL FINAL PARA SOBRESCRIBIR TODO ***
                // Bordes solo a la tabla (desde headers hasta última fila de datos)
                $sheet->getStyle("A{$headerRow1}:{$lastColLetter}{$this->lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
            },
        ];
    }
}