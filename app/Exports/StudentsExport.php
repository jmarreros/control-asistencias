<?php

namespace App\Exports;

use App\Models\Student;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    private const QUOTA_LABELS = [
        'full1' => 'Full-1 (ilimitado)',
        'full2' => 'Full-2 (ilimitado)',
    ];

    private const STATUS_LABELS = [
        'ok'        => 'Activo',
        'exhausted' => 'Agotado',
        'expired'   => 'Vencido',
        'pending'   => 'Pendiente',
    ];

    public function collection()
    {
        return Student::with('currentPlan')->orderBy('name')->get();
    }

    public function headings(): array
    {
        return [
            'Nombre',
            'DNI',
            'Teléfono',
            'Alumno activo',
            'Tipo de plan',
            'Estado del plan',
            'Clases restantes',
            'Fecha inicio',
            'Fecha fin',
            'Promoción',
        ];
    }

    public function map($student): array
    {
        $plan = $student->currentPlan;

        return [
            $student->name,
            $student->dni  ?? '—',
            $student->phone ?? '—',
            $student->active ? 'Sí' : 'No',
            $plan ? (self::QUOTA_LABELS[$plan->class_quota] ?? $plan->class_quota . ' clases') : '—',
            $plan ? (self::STATUS_LABELS[$plan->status()] ?? '—') : '—',
            $plan ? ($plan->classesRemaining() !== null ? $plan->classesRemaining() : 'Ilimitadas') : '—',
            $plan ? Carbon::parse($plan->start_date)->format('d/m/Y') : '—',
            $plan ? Carbon::parse($plan->end_date)->format('d/m/Y') : '—',
            $plan ? ($plan->promotionLabel() ?? '—') : '—',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4f46e5']],
            ],
        ];
    }

    public function title(): string
    {
        return 'Alumnos ' . now()->format('d-m-Y');
    }
}
