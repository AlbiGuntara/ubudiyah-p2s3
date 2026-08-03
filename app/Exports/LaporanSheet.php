<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LaporanSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    protected array $data;
    protected string $title;
    protected string $periode;
    protected string $section;
    protected string $sectionLabel;

    public const ROW_TITLE = 1;
    public const ROW_SUBTITLE = 2;
    public const ROW_PERIODE = 3;
    public const ROW_BLANK = 4;
    public const ROW_HEADER = 5;
    public const ROW_DATA_START = 6;

    public function __construct(array $data, string $title, string $periode, string $section)
    {
        $this->data = $data;
        $this->title = $title;
        $this->periode = $periode;
        $this->section = $section;

        $labels = [
            'ringkasan' => 'RINGKASAN LAPORAN',
            'per_daerah' => 'REKAP PER DAERAH',
            'per_asrama' => 'REKAP PER ASRAMA',
            'per_jenis_pelanggaran' => 'REKAP PER JENIS PELANGGARAN',
            'per_iksass' => 'REKAP PER IKSASS',
            'per_nama' => 'REKAP PER NAMA (10 BESAR)',
        ];
        $this->sectionLabel = $labels[$section] ?? strtoupper($title);
    }

    public function title(): string
    {
        return $this->title;
    }

    public function array(): array
    {
        $rows = [];
        $rows[] = ["PONDOK PESANTREN SALAFIYAH SYAFI'IYAH SUKOREJO"];
        $rows[] = ['LAPORAN PELANGGARAN - ' . $this->sectionLabel];
        $rows[] = ['Periode: ' . $this->periode];
        $rows[] = [];
        $rows[] = $this->getHeaders();
        foreach ($this->getDataRows() as $r) {
            $rows[] = $r;
        }
        if ($this->hasTotal()) {
            $rows[] = [];
            $rows[] = $this->getTotalRow();
        }
        return $rows;
    }

    protected function getHeaders(): array
    {
        return match ($this->section) {
            'ringkasan' => ['No', 'Indikator', 'Nilai'],
            'per_daerah' => ['No', 'Kode', 'Daerah', 'Jumlah Pelanggaran', 'Jumlah Pelanggar', 'Total Santri'],
            'per_asrama' => ['No', 'Daerah', 'Asrama', 'Jumlah Pelanggaran', 'Jumlah Pelanggar', 'Total Santri'],
            'per_jenis_pelanggaran' => ['No', 'Jenis Pelanggaran', 'Jumlah Pelanggaran', 'Jumlah Santri'],
            'per_iksass' => ['No', 'IKSASS', 'Jumlah Pelanggaran', 'Jumlah Pelanggar', 'Total Santri'],
            'per_nama' => ['No', 'Nama Santri', 'NIS', 'IKSASS', 'Asrama', 'Jumlah Pelanggaran'],
            default => [],
        };
    }

    protected function getDataRows(): array
    {
        $rows = [];
        $no = 1;

        switch ($this->section) {
            case 'ringkasan':
                $r = $this->data['ringkasan'] ?? [];
                $rows[] = [$no++, 'Total Pelanggaran', $r['total_pelanggaran'] ?? 0];
                $rows[] = [$no++, 'Total Santri Melanggar', $r['total_santri'] ?? 0];
                $rows[] = [$no++, 'Sumber Petugas', $r['sumber_petugas'] ?? 0];
                $rows[] = [$no++, 'Sumber Ketua Kamar', $r['sumber_ketua_kamar'] ?? 0];
                $ps = $this->data['per_sumber'] ?? [];
                $p = isset($ps['petugas']) ? $ps['petugas']->jumlah_pelanggaran ?? 0 : 0;
                $s = isset($ps['petugas']) ? $ps['petugas']->jumlah_santri ?? 0 : 0;
                $pk = isset($ps['ketua_kamar']) ? $ps['ketua_kamar']->jumlah_pelanggaran ?? 0 : 0;
                $sk = isset($ps['ketua_kamar']) ? $ps['ketua_kamar']->jumlah_santri ?? 0 : 0;
                $rows[] = [$no++, 'Rincian Petugas - Jumlah Pelanggaran', $p];
                $rows[] = [$no++, 'Rincian Petugas - Jumlah Santri', $s];
                $rows[] = [$no++, 'Rincian Ketua Kamar - Jumlah Pelanggaran', $pk];
                $rows[] = [$no++, 'Rincian Ketua Kamar - Jumlah Santri', $sk];
                break;

            case 'per_daerah':
                foreach ($this->data['per_daerah'] ?? [] as $item) {
                    $rows[] = [$no++, $item->kode, $item->nama_daerah, (int) $item->jumlah_pelanggaran, (int) $item->jumlah_santri, (int) $item->total_santri];
                }
                break;

            case 'per_asrama':
                foreach ($this->data['per_asrama'] ?? [] as $item) {
                    $label = $item->daerah_kode ? substr($item->daerah_kode, 0, 1) . '.' . $item->nomor : $item->nomor;
                    $rows[] = [$no++, $item->nama_daerah, $label, (int) $item->jumlah_pelanggaran, (int) $item->jumlah_santri, (int) $item->total_santri];
                }
                break;

            case 'per_jenis_pelanggaran':
                foreach ($this->data['per_jenis_pelanggaran'] ?? [] as $item) {
                    $rows[] = [$no++, $item->nama_pelanggaran, (int) $item->jumlah_pelanggaran, (int) $item->jumlah_santri];
                }
                break;

            case 'per_iksass':
                foreach ($this->data['per_iksass'] ?? [] as $item) {
                    $rows[] = [$no++, $item->iksass ?: '-', (int) $item->jumlah_pelanggaran, (int) $item->jumlah_santri, (int) $item->total_santri];
                }
                break;

            case 'per_nama':
                foreach ($this->data['per_nama'] ?? [] as $item) {
                    $rows[] = [$no++, $item->nama, $item->nis ?? '-', $item->iksass ?? '-', $item->asrama ?? '-', (int) $item->jumlah_pelanggaran];
                }
                break;
        }

        return $rows;
    }

    protected function hasTotal(): bool
    {
        return in_array($this->section, [
            'per_daerah', 'per_asrama', 'per_jenis_pelanggaran', 'per_iksass', 'per_nama',
        ]) && count($this->getDataRows()) > 0;
    }

    protected function getTotalRow(): array
    {
        $sums = $this->sumColumns();
        return match ($this->section) {
            'per_daerah' => ['', '', 'TOTAL', $sums[0], $sums[1], $sums[2]],
            'per_asrama' => ['', '', 'TOTAL', $sums[0], $sums[1], $sums[2]],
            'per_jenis_pelanggaran' => ['', '', 'TOTAL', $sums[0], $sums[1]],
            'per_iksass' => ['', 'TOTAL', $sums[0], $sums[1], $sums[2]],
            'per_nama' => ['', 'TOTAL', '', '', '', $sums[0]],
            default => [],
        };
    }

    protected function sumColumns(): array
    {
        $dataRows = $this->getDataRows();

        return match ($this->section) {
            'per_daerah', 'per_asrama' => [
                collect($dataRows)->sum(3) ?: 0,
                collect($dataRows)->sum(4) ?: 0,
                collect($dataRows)->sum(5) ?: 0,
            ],
            'per_jenis_pelanggaran' => [
                collect($dataRows)->sum(3) ?: 0,
                collect($dataRows)->sum(4) ?: 0,
            ],
            'per_iksass' => [
                collect($dataRows)->sum(2) ?: 0,
                collect($dataRows)->sum(3) ?: 0,
                collect($dataRows)->sum(4) ?: 0,
            ],
            'per_nama' => [
                collect($dataRows)->sum(5) ?: 0,
            ],
            default => [],
        };
    }

    protected function colLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = (int)(($index - $mod) / 26);
        }
        return $letter;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $headerCount = count($this->getHeaders());
                $lastCol = $this->colLetter($headerCount);
                $hasTotal = $this->hasTotal();

                $dataEnd = $hasTotal ? $highestRow - 2 : $highestRow;

                $dark = '2D2D2D';
                $medium = '555555';
                $light = 'FAFAFA';
                $border = 'CCCCCC';

                if ($headerCount > 1) {
                    $sheet->mergeCells('A1:' . $lastCol . '1');
                }
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => $dark]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(28);

                if ($headerCount > 1) {
                    $sheet->mergeCells('A2:' . $lastCol . '2');
                }
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => $medium]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                if ($headerCount > 1) {
                    $sheet->mergeCells('A3:' . $lastCol . '3');
                }
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['size' => 10, 'italic' => true, 'color' => ['rgb' => '999999']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $headerRange = 'A5:' . $lastCol . '5';
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $dark]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $dark]],
                    ],
                ]);
                $sheet->getRowDimension(5)->setRowHeight(20);

                if ($dataEnd >= 6) {
                    $dataRange = 'A6:' . $lastCol . $dataEnd;

                    $sheet->getStyle($dataRange)->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $border]],
                        ],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);

                    for ($r = 6; $r <= $dataEnd; $r++) {
                        $range = 'A' . $r . ':' . $lastCol . $r;
                        if (($r - 6) % 2 === 1) {
                            $sheet->getStyle($range)->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $light]],
                            ]);
                        }
                    }

                    $sheet->getStyle('A6:A' . $dataEnd)->applyFromArray([
                        'font' => ['bold' => true],
                    ]);

                    $sheet->getStyle('A6:A' . $dataEnd)->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $numColIndices = match ($this->section) {
                        'ringkasan' => [3],
                        'per_daerah' => [4, 5, 6],
                        'per_asrama' => [4, 5, 6],
                        'per_jenis_pelanggaran' => [4, 5],
                        'per_iksass' => [3, 4, 5],
                        'per_nama' => [6],
                        default => [],
                    };
                    foreach ($numColIndices as $idx) {
                        $c = $this->colLetter($idx);
                        $sheet->getStyle($c . '6:' . $c . $dataEnd)->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                }

                if ($hasTotal) {
                    $totalRow = $highestRow;
                    $totalRange = 'A' . $totalRow . ':' . $lastCol . $totalRow;
                    $sheet->getStyle($totalRange)->applyFromArray([
                        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $medium]],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => $medium]],
                        ],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getStyle('A' . $totalRow . ':A' . $totalRow)
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                for ($c = 'A'; $c <= $lastCol; $c++) {
                    $sheet->getColumnDimension($c)->setAutoSize(true);
                }
            },
        ];
    }
}
