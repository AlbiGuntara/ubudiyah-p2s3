<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePelanggaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'santri_id' => 'nullable|exists:santri,id',
            'asrama_id' => 'required|exists:asrama,id',
            'daftar_pelanggaran_id' => 'required|exists:daftar_pelanggaran,id',
            'petugas_id' => 'required|exists:petugas,id',
            'jumlah' => 'required|integer|min:1',
            'sumber_pencatatan' => 'required|in:petugas,ketua_kamar',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
        ];
    }
}
