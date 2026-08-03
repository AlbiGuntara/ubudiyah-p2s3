<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LaporanSingleExport implements WithMultipleSheets
{
    protected array $data;
    protected string $periode;
    protected string $section;

    public function __construct(array $data, string $periode, string $section)
    {
        $this->data = $data;
        $this->periode = $periode;
        $this->section = $section;
    }

    public function sheets(): array
    {
        $label = match ($this->section) {
            'per_daerah' => 'Per Daerah',
            'per_asrama' => 'Per Asrama',
            'per_jenis_pelanggaran' => 'Per Jenis Pelanggaran',
            'per_iksass' => 'Per IKSASS',
            'per_nama' => 'Per Nama',
        };

        return [
            new LaporanSheet($this->data, $label, $this->periode, $this->section),
        ];
    }
}
