<?php
namespace App\Imports;

use App\Models\Santri;
use App\Models\Asrama;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SantriImport implements ToModel, WithHeadingRow
{
    private array $errors = [];
    private int $row = 1;
    private int $imported = 0;

    public function model(array $row)
    {
        $this->row++;

        $nama = trim($row['nama'] ?? '');
        $nis = trim($row['nis'] ?? '');
        $iksass = trim($row['iksass'] ?? '');
        $asramaNomor = trim($row['asrama'] ?? '');

        if (empty($nama)) {
            $this->errors[] = "Baris {$this->row}: Nama tidak boleh kosong";
            return null;
        }

        if (empty($asramaNomor)) {
            $this->errors[] = "Baris {$this->row}: Asrama tidak boleh kosong";
            return null;
        }

        $asrama = Asrama::where('nomor', $asramaNomor)->first();
        if (!$asrama) {
            $this->errors[] = "Baris {$this->row}: Asrama dengan nomor \"{$asramaNomor}\" tidak ditemukan";
            return null;
        }

        if (!empty($nis)) {
            $existing = Santri::where('nis', $nis)->whereNull('deleted_at')->first();
            if ($existing) {
                $this->errors[] = "Baris {$this->row}: NIS \"{$nis}\" sudah terdaftar atas nama {$existing->nama}";
                return null;
            }
        }

        $this->imported++;

        return new Santri([
            'nama' => $nama,
            'nis' => $nis ?: null,
            'iksass' => $iksass ?: null,
            'asrama_id' => $asrama->id,
        ]);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getImportedCount(): int
    {
        return $this->imported;
    }
}
