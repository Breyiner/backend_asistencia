<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendancesExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        protected int $year,
        protected int $month,
        protected int $fichaId,
    ) {}

    public function headings(): array
    {
        return [
            'Documento',
            'Nombre',
            'Fecha',
            'Estado',
            'Horas',
        ];
    }

    public function query()
    {
        $startDate = "{$this->year}-" . str_pad($this->month, 2, '0', STR_PAD_LEFT) . "-01";
        $endDate   = date('Y-m-t', strtotime($startDate));

        return Attendance::query()
            ->with(['apprentice.profile', 'realClass'])
            ->whereHas('apprentice', fn($q) => $q->where('ficha_id', $this->fichaId))
            ->whereHas('realClass', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('execution_date', [$startDate, $endDate]); // ✅
            })
            ->orderBy('apprentice_id')
            ->orderBy(
                \App\Models\RealClass::select('execution_date')
                    ->whereColumn('real_classes.id', 'attendances.real_class_id')
            );
    }

    public function map($attendance): array
    {
        return [
            $attendance->apprentice->document ?? '',
            $attendance->apprentice->profile->full_name ?? '',
            $attendance->realClass?->execution_date,
            $attendance->status, // 'present', 'absent', 'justified', etc.
            $attendance->hours ?? 0,
        ];
    }
}
