<?php

namespace App\Exports;

use App\Models\StudentPlan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class EarningsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    public function __construct(
        private string $from,
        private string $to
    ) {}

    public function collection()
    {
        return StudentPlan::with('student')
            ->whereBetween('created_at', [$this->from . ' 00:00:00', $this->to . ' 23:59:59'])
            ->whereNotNull('price')
            ->orderBy('created_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Alumno',
            'Tipo de plan',
            'Monto (S/)',
            'Fecha inicio',
            'Fecha fin',
        ];
    }

    public function map($plan): array
    {
        return [
            $plan->student->name,
            $plan->class_quota === 'full' ? 'Full (ilimitado)' : $plan->class_quota . ' clases',
            number_format($plan->price, 2),
            Carbon::parse($plan->start_date)->format('d/m/Y'),
            Carbon::parse($plan->end_date)->format('d/m/Y'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '059669']],
            ],
        ];
    }

    public function title(): string
    {
        return 'Ganancias ' . $this->from . ' al ' . $this->to;
    }
}
