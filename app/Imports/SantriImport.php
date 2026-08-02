<?php
namespace App\Imports;

use App\Models\Santri;
use App\Models\Asrama;
use App\Models\Daerah;
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
        $daerahInput = trim($row['daerah'] ?? '');
        $status = strtolower(trim($row['status'] ?? ''));

        if (!in_array($status, ['', 'aktif', 'tidak aktif', 'berhenti'])) {
            $this->errors[] = "Baris {$this->row}: Status \"{$status}\" tidak valid (harus aktif, tidak aktif, atau berhenti)";
            return null;
        }

        if (empty($nama)) {
            $this->errors[] = "Baris {$this->row}: Nama tidak boleh kosong";
            return null;
        }

        if (empty($asramaNomor)) {
            $this->errors[] = "Baris {$this->row}: Asrama tidak boleh kosong";
            return null;
        }

        $asramaQuery = Asrama::where('nomor', $asramaNomor);

        if (!empty($daerahInput)) {
            $daerah = Daerah::where('kode', $daerahInput)
                ->orWhere('nama_daerah', $daerahInput)
                ->first();

            if (!$daerah) {
                $this->errors[] = "Baris {$this->row}: Daerah \"{$daerahInput}\" tidak ditemukan";
                return null;
            }

            $asramaQuery->where('daerah_id', $daerah->id);
        }

        $asrama = $asramaQuery->first();
        if (!$asrama) {
            $msg = "Baris {$this->row}: Asrama dengan nomor \"{$asramaNomor}\"";
            if (!empty($daerahInput)) {
                $msg .= " di daerah \"{$daerahInput}\"";
            }
            $msg .= " tidak ditemukan";
            $this->errors[] = $msg;
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
            'status' => $status ?: 'aktif',
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
