<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SOPsAndCallRateExport implements FromCollection, WithHeadings, WithStyles
{
    use Exportable;

    private $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function collection()
    {
        return $this->query->get()->map(function ($record) {
            return [
                $record->id,
                $record->name,
                $record->area_name,
                $record->working_days,
                $record->daily_visit_target,
                $record->office_work_count,
                $record->activities_count,
                $record->actual_working_days,
                $record->monthly_visit_target,
                number_format($record->sops, 2),
                $record->actual_visits,
                number_format($record->call_rate, 2),
                $record->total_visits,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Medical Rep',
            'Area',
            'Working Days',
            'Daily Visit Target',
            'Office Work',
            'Activities',
            'Actual Working Days',
            'Monthly Visits Target',
            'SOPs %',
            'Actual Visits',
            'Call Rate',
            'Total Visits',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(18);
        $sheet->getColumnDimension('J')->setWidth(10);
        $sheet->getColumnDimension('K')->setWidth(12);
        $sheet->getColumnDimension('L')->setWidth(10);
        $sheet->getColumnDimension('M')->setWidth(12);

        // Style the header row
        $sheet->getStyle('A1:M1')->applyFromArray([
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

        // Set row height for header
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Style the data rows
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("A2:M{$lastRow}")->applyFromArray([
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

        // Apply conditional formatting for data rows
        for ($row = 2; $row <= $lastRow; $row++) {
            // Get the values for conditional formatting
            $sops = $sheet->getCell("J{$row}")->getValue();
            $callRate = $sheet->getCell("L{$row}")->getValue();
            $actualVisits = $sheet->getCell("K{$row}")->getValue();
            $targetVisits = $sheet->getCell("I{$row}")->getValue();

            // Calculate productivity (actual visits vs target)
            $productivity = $targetVisits > 0 ? ($actualVisits / $targetVisits) * 100 : 0;

            // Apply row background color based on productivity
            if ($productivity < 50) {
                $sheet->getStyle("A{$row}:M{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FFE6E6'); // Light red for low productivity
            } elseif ($productivity < 75) {
                $sheet->getStyle("A{$row}:M{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FFF2E6'); // Light orange for medium productivity
            }

            // Style SOPs percentage
            if ($sops < 50) {
                $sheet->getStyle("J{$row}")->getFont()->setColor(new Color(Color::COLOR_RED));
            } elseif ($sops < 75) {
                $sheet->getStyle("J{$row}")->getFont()->setColor(new Color(Color::COLOR_DARKYELLOW));
            } else {
                $sheet->getStyle("J{$row}")->getFont()->setColor(new Color(Color::COLOR_DARKGREEN));
            }

            // Style call rate
            if ($callRate < 50) {
                $sheet->getStyle("L{$row}")->getFont()->setColor(new Color(Color::COLOR_RED));
            } elseif ($callRate < 75) {
                $sheet->getStyle("L{$row}")->getFont()->setColor(new Color(Color::COLOR_DARKYELLOW));
            } else {
                $sheet->getStyle("L{$row}")->getFont()->setColor(new Color(Color::COLOR_DARKGREEN));
            }

            // Add percentage symbol to SOPs and call rate
            $sheet->setCellValue("J{$row}", $sops . '%');
            $sheet->setCellValue("L{$row}", $callRate . '%');
        }

        // Freeze the header row
        $sheet->freezePane('A2');
    }
}
