<?php
namespace App\Imports;

use App\Models\Santri;
use App\Models\Asrama;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class SantriImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        $asrama = Asrama::where('nomor', $row['asrama'])->first();

        if (!$asrama) {
            return null;
        }

        return new Santri([
            'nama' => $row['nama'],
            'nis' => $row['nis'] ?? null,
            'iksass' => $row['iksass'] ?? null,
            'asrama_id' => $asrama->id,
        ]);
    }

    public function rules(): array
    {
        return [
            'nama' => 'required|string|max:100',
            'asrama' => 'required',
        ];
    }
}
