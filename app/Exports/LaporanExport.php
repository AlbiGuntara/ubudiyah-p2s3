<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LaporanExport implements WithMultipleSheets
{
    protected array $data;
    protected string $periode;

    public function __construct(array $data, string $periode)
    {
        $this->data = $data;
        $this->periode = $periode;
    }

    public function sheets(): array
    {
        return [
            new LaporanSheet($this->data, 'Ringkasan', $this->periode, 'ringkasan'),
            new LaporanSheet($this->data, 'Per Daerah', $this->periode, 'per_daerah'),
            new LaporanSheet($this->data, 'Per Asrama', $this->periode, 'per_asrama'),
            new LaporanSheet($this->data, 'Per Jenis Pelanggaran', $this->periode, 'per_jenis_pelanggaran'),
            new LaporanSheet($this->data, 'Per IKSASS', $this->periode, 'per_iksass'),
            new LaporanSheet($this->data, 'Per Nama', $this->periode, 'per_nama'),
        ];
    }
}
