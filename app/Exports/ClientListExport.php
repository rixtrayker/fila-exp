<?php

namespace App\Exports;

use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exports a user's personal client list with the fields the field team
 * evaluates against: client name, type, class (A/B/C), brick and the
 * governorate / region resolved through brick -> city -> governorate -> region.
 */
class ClientListExport implements FromCollection, WithHeadings, WithStyles
{
    use Exportable;

    private $query;
    private $userName;

    public function __construct($query, ?string $userName = null)
    {
        $this->query = $query;
        $this->userName = $userName;
    }

    public function collection()
    {
        try {
            $data = $this->query->get();

            if ($data->isEmpty()) {
                return collect([
                    ['No clients found in this list']
                ]);
            }

            return $data->map(function ($client) {
                $governorate = $client->brick?->city?->governorate;

                return [
                    $client->name_en ?? '',
                    $client->name_ar ?? '',
                    $client->clientType?->name ?? '',
                    $client->grade ?? '',
                    $client->brick?->name ?? '',
                    $governorate?->name ?? '',
                    $governorate?->region?->name ?? '',
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
            'Client Name',
            'Client Name (العربية)',
            'Client Type',
            'Class',
            'Brick',
            'Governorate',
            'Region',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(10);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(20);

        // Style the header row
        $sheet->getStyle('A1:G1')->applyFromArray([
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
            $sheet->getStyle("A2:G{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'DDDDDD'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Center the class / brick / geography columns
            $sheet->getStyle("C2:G{$lastRow}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Freeze the header row
        $sheet->freezePane('A2');
    }

    /**
     * Generate filename for the export
     */
    public function getFilename(): string
    {
        $owner = Str::slug($this->userName ?? 'user', '_');

        return 'client_list_' . $owner . '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
    }
}
