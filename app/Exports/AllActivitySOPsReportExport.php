<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AllActivitySOPsReportExport implements FromCollection, WithHeadings, WithStyles
{
    use Exportable;

    private Collection $rows;
    private ?string $dateRange;

    public function __construct(Collection $rows, ?string $dateRange = null)
    {
        $this->rows = $rows;
        $this->dateRange = $dateRange;
    }

    public function collection()
    {
        if ($this->rows->isEmpty()) {
            return collect([
                ['No data available for the selected criteria'],
            ]);
        }

        return $this->rows->map(function ($row) {
            $row = collect($row);

            return [
                $row->get('id', ''),
                $row->get('name', ''),
                $row->get('role_name', ''),
                $row->get('working_days', 0),
                $row->get('actual_working_days', 0),
                $row->get('am_visits', 0),
                $row->get('am_daily_target', 0),
                $row->get('am_monthly_target', 0),
                $row->get('am_sops', 0),
                $row->get('pm_visits', 0),
                $row->get('pm_daily_target', 0),
                $row->get('pm_monthly_target', 0),
                $row->get('pm_sops', 0),
                $row->get('ph_visits', 0),
                $row->get('ph_daily_target', 0),
                $row->get('ph_monthly_target', 0),
                $row->get('ph_sops', 0),
                $row->get('total_visits', 0),
                $row->get('call_rate', 0),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'User',
            'Role',
            'Working Days',
            'Actual Working Days',
            'AM Visits',
            'AM Daily Target',
            'AM Monthly Target',
            'AM SOPs %',
            'PM Visits',
            'PM Daily Target',
            'PM Monthly Target',
            'PM SOPs %',
            'PH Visits',
            'PH Daily Target',
            'PH Monthly Target',
            'PH SOPs %',
            'Total Visits',
            'Call Rate',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = 'S';

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(18);

        foreach (range('D', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setWidth(14);
        }

        // Style the header row
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2C3E50'],
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

        $sheet->getRowDimension(1)->setRowHeight(25);

        // Style the data rows
        $lastRow = $sheet->getHighestRow();
        if ($lastRow > 1) {
            $sheet->getStyle("A2:{$lastColumn}{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'DDDDDD'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        // Freeze the header row
        $sheet->freezePane('A2');
    }

    /**
     * Generate filename for the export
     */
    public function getFilename(): string
    {
        $dateRange = $this->dateRange ?? now()->format('Y-m-d_H-i-s');

        return 'all_activity_sops_report_' . $dateRange . '.xlsx';
    }
}
