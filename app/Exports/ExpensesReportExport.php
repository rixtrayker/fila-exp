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

class ExpensesReportExport implements FromCollection, WithHeadings, WithStyles
{
    use Exportable;

    private $query;
    private $dateRange;

    public function __construct($query, $dateRange = null)
    {
        $this->query = $query;
        $this->dateRange = $dateRange;
    }

    public function collection()
    {
        try {
            $data = $this->query->get();

            if ($data->isEmpty()) {
                return collect([
                    ['No data available for the selected criteria']
                ]);
            }

            return $data->map(function ($record) {
                return [
                    $record->id ?? '',
                    $record->medical_rep ?? '',
                    number_format($record->transportation ?? 0, 2),
                    number_format($record->accommodation ?? 0, 2),
                    $record->distance ?? 0, // Distance should be whole number (km)
                    number_format($record->meal ?? 0, 2),
                    number_format($record->telephone_postage ?? 0, 2),
                    number_format($record->daily_allowance ?? 0, 2),
                    number_format($record->medical_expenses ?? 0, 2),
                    number_format($record->others ?? 0, 2),
                    number_format($record->total ?? 0, 2),
                ];
            });
        } catch (\Exception $e) {
            // Return error message if data collection fails
            return collect([
                ['Error collecting data: ' . $e->getMessage()]
            ]);
        }
    }

    public function headings(): array
    {
        return [
            'ID',
            'Medical Rep',
            'Transportation',
            'Accommodation',
            'Distance (km)',
            'Meal',
            'Postage/Telephone/Fax',
            'Daily Allowance',
            'Medical Expenses',
            'Others',
            'Total',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(15);
        $sheet->getColumnDimension('J')->setWidth(12);
        $sheet->getColumnDimension('K')->setWidth(15);

        // Style the header row
        $sheet->getStyle('A1:K1')->applyFromArray([
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
        if ($lastRow > 1) {
            $sheet->getStyle("A2:K{$lastRow}")->applyFromArray([
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

            // Apply conditional formatting for high expense totals
            for ($row = 2; $row <= $lastRow; $row++) {
                $total = $sheet->getCell("K{$row}")->getValue();
                $totalNumeric = (float) str_replace(',', '', $total);

                // Apply row background color based on expense total
                if ($totalNumeric > 1000) {
                    $sheet->getStyle("A{$row}:K{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('FFE6E6'); // Light red for high expenses
                } elseif ($totalNumeric > 500) {
                    $sheet->getStyle("A{$row}:K{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('FFF2E6'); // Light orange for medium expenses
                } else {
                    $sheet->getStyle("A{$row}:K{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('E6FFE6'); // Light green for low expenses
                }

                // Style total column with bold and color based on amount
                if ($totalNumeric > 1000) {
                    $sheet->getStyle("K{$row}")->getFont()
                        ->setBold(true)
                        ->setColor(new Color(Color::COLOR_RED));
                } elseif ($totalNumeric > 500) {
                    $sheet->getStyle("K{$row}")->getFont()
                        ->setBold(true)
                        ->setColor(new Color(Color::COLOR_DARKYELLOW));
                } else {
                    $sheet->getStyle("K{$row}")->getFont()
                        ->setBold(true)
                        ->setColor(new Color(Color::COLOR_DARKGREEN));
                }
            }
        }

        // Freeze the header row
        $sheet->freezePane('A2');

        // Add a summary row at the bottom if there are records
        if ($lastRow > 1) {
            $summaryRow = $lastRow + 2;

            // Add summary labels
            $sheet->setCellValue("A{$summaryRow}", 'Summary');
            $sheet->setCellValue("B{$summaryRow}", 'Total Records: ' . ($this->getRecordCount()));
            $sheet->setCellValue("C{$summaryRow}", 'Total Amount: ' . number_format($this->getTotalSum(), 2));

            // Style summary row
            $sheet->getStyle("A{$summaryRow}:C{$summaryRow}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F8F9FA'],
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

    /**
     * Generate filename for the export
     */
    public function getFilename(): string
    {
        $dateRange = $this->dateRange ?? 'all_dates';
        return 'expenses_report_' . $dateRange . '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
    }

    /**
     * Get total sum of all expenses
     */
    public function getTotalSum(): float
    {
        return $this->query->sum('total') ?? 0;
    }

    /**
     * Get count of records
     */
    public function getRecordCount(): int
    {
        return $this->query->count() ?? 0;
    }
}
