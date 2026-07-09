<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SamplesReportExport implements FromCollection, WithHeadings, WithStyles
{
    use Exportable;

    private $query;
    private $dateRange;
    private $records;

    public function __construct($query, $dateRange = null)
    {
        $this->query = $query;
        $this->dateRange = $dateRange;
    }

    public function collection()
    {
        try {
            $data = $this->getRecords();

            if ($data->isEmpty()) {
                return collect([
                    ['No data available for the selected criteria']
                ]);
            }

            return $data->map(function ($record) {
                return [
                    $record->visit_date ? $record->visit_date->format('Y-m-d') : '',
                    $record->medical_rep ?? '',
                    $record->client_name ?? '',
                    $record->product_name ?? '',
                    $record->samples_count ?? 0,
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
            'Visit Date',
            'Delivered By',
            'Client',
            'Product',
            'Samples',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(12);

        // Style the header row
        $sheet->getStyle('A1:E1')->applyFromArray([
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
            $sheet->getStyle("A2:E{$lastRow}")->applyFromArray([
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

            // Bold the samples column
            $sheet->getStyle("E2:E{$lastRow}")->getFont()->setBold(true);
        }

        // Freeze the header row
        $sheet->freezePane('A2');

        // Add a summary row at the bottom if there are records
        if ($lastRow > 1) {
            $summaryRow = $lastRow + 2;

            // Add summary labels
            $sheet->setCellValue("A{$summaryRow}", 'Summary');
            $sheet->setCellValue("B{$summaryRow}", 'Medical Reps: ' . $this->getMedicalRepsCount());
            $sheet->setCellValue("C{$summaryRow}", 'Records: ' . $this->getRecordCount());
            $sheet->setCellValue("D{$summaryRow}", 'Products: ' . $this->getProductsCount());
            $sheet->setCellValue("E{$summaryRow}", 'Total Samples: ' . $this->getTotalSum());

            // Style summary row
            $sheet->getStyle("A{$summaryRow}:E{$summaryRow}")->applyFromArray([
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
        return 'samples_report_' . $dateRange . '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
    }

    /**
     * Get total sum of distributed samples
     */
    public function getTotalSum(): int
    {
        return $this->getRecords()->sum('samples_count');
    }

    /**
     * Get count of records
     */
    public function getRecordCount(): int
    {
        return $this->getRecords()->count();
    }

    /**
     * Get count of medical reps in the report
     */
    public function getMedicalRepsCount(): int
    {
        return $this->getRecords()->pluck('user_id')->unique()->count();
    }

    /**
     * Get count of products in the report
     */
    public function getProductsCount(): int
    {
        return $this->getRecords()->pluck('product_name')->unique()->count();
    }

    /**
     * Fetch and cache the report records
     */
    private function getRecords()
    {
        if ($this->records === null) {
            $this->records = $this->query->get();
        }

        return $this->records;
    }
}
