<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Exportador de errores de importación de aprendices.
 *
 * Replica el estilo exacto de la plantilla original (plantilla_aprendices.xlsx):
 * - Cabeceras: fondo #F2F2F2, negrita, tamaño 11, bordes thin, centrado
 * - Filas de datos: sin fondo, bordes thin, centrado
 * - Anchos de columna idénticos a la plantilla
 *
 * Agrega una columna extra "errores" al final con fondo rojo claro
 * para identificar rápidamente qué falló en cada fila.
 *
 * Uso:
 *   return Excel::download(new ImportErrorsExport($errors), 'aprendices_con_errores.xlsx');
 */
class ImportErrorsExport implements FromArray, WithHeadings, WithColumnWidths, WithEvents
{
    public function __construct(private array $errors) {}

    /**
     * Cabeceras — mismas que la plantilla + columna errores al final.
     */
    public function headings(): array
    {
        return [
            'nombres',
            'apellidos',
            'telefono',
            'tipo_documento',
            'numero_documento',
            'email',
            'fecha_nacimiento',
            'numero_ficha',
            'errores',
        ];
    }

    /**
     * Filas de datos con la misma estructura de la plantilla original.
     * Los valores vienen de $item['valores'] tal cual los mandó el backend.
     */
    public function array(): array
    {
        return array_map(fn($item) => [
            $item['valores']['nombres']          ?? '',
            $item['valores']['apellidos']        ?? '',
            $item['valores']['telefono']         ?? '',
            $item['valores']['tipo_documento']   ?? '',
            $item['valores']['numero_documento'] ?? '',
            $item['valores']['email']            ?? '',
            $item['valores']['fecha_nacimiento'] ?? '',
            $item['valores']['numero_ficha']     ?? '',
            implode(' | ', $item['errores']),
        ], $this->errors);
    }

    /**
     * Anchos de columna idénticos a la plantilla original.
     * Columna I (errores) más ancha para los mensajes.
     */
    public function columnWidths(): array
    {
        return [
            'A' => 16.14,   // nombres
            'B' => 15.43,   // apellidos
            'C' => 19.57,   // telefono
            'D' => 21.86,   // tipo_documento
            'E' => 24.29,   // numero_documento
            'F' => 31.86,   // email
            'G' => 23.57,   // fecha_nacimiento
            'H' => 16.86,   // numero_ficha
            'I' => 40.00,   // errores
        ];
    }

    /**
     * Aplica estilos después de generar la hoja.
     *
     * Fila 1 (cabeceras):
     *   A-H → fondo #F2F2F2, negrita 11, bordes thin, centrado  (igual a la plantilla)
     *   I   → fondo #FEE2E2, negrita 11, texto rojo, bordes thin (columna extra de errores)
     *
     * Filas 2-N (datos):
     *   A-H → sin fondo, bordes thin, centrado                   (igual a la plantilla)
     *   I   → fondo #FEF2F2, texto rojo, alineado a la izquierda con wrap
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastRow = count($this->errors) + 1; // +1 por la cabecera

                // ── Cabeceras A-H (idéntico a la plantilla) ───────────────
                $sheet->getStyle('A1:H1')->applyFromArray([
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F2F2F2'],
                    ],
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // ── Cabecera columna errores (I1) ─────────────────────────
                $sheet->getStyle('I1')->applyFromArray([
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FEE2E2'],
                    ],
                    'font' => [
                        'bold'  => true,
                        'size'  => 11,
                        'color' => ['rgb' => 'DC2626'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                if ($lastRow < 2) return;

                // ── Datos A-H (idéntico a la plantilla) ───────────────────
                $sheet->getStyle("A2:H{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // ── Datos columna errores (I) — rojo suave ────────────────
                $sheet->getStyle("I2:I{$lastRow}")->applyFromArray([
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FEF2F2'],
                    ],
                    'font' => [
                        'color' => ['rgb' => 'DC2626'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
                    ],
                ]);
            },
        ];
    }
}