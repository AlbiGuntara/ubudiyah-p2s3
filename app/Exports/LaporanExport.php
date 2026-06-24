<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    protected array $data;
    protected string $judul;
    protected string $periode;

    public function __construct(array $data, string $judul, string $periode)
    {
        $this->data = $data;
        $this->judul = $judul;
        $this->periode = $periode;
    }

    public function array(): array
    {
        return [
            [$this->judul],
            ['Periode: ' . $this->periode],
            [],
            ['Jumlah Pelanggaran', $this->data['jumlah_pelanggaran'] ?? 0],
            ['Jumlah Santri Melanggar', $this->data['jumlah_santri'] ?? 0],
        ];
    }

    public function headings(): array
    {
        return [];
    }

    public function title(): string
    {
        return $this->judul;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
        ];
    }
}
