<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePelanggaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->isMethod('POST')) {
            return $this->user()->can('create_pelanggaran');
        }
        return $this->user()->can('edit_pelanggaran');
    }

    public function rules(): array
    {
        $rules = [
            'santri_id' => 'nullable|exists:santri,id',
            'santri_pelanggaran' => 'nullable|array',
            'santri_pelanggaran.*.santri_id' => 'required|exists:santri,id',
            'santri_pelanggaran.*.daftar_pelanggaran_id' => 'required|exists:daftar_pelanggaran,id',
            'anonymous_entries' => 'nullable|array',
            'anonymous_entries.*.jumlah' => 'required|integer|min:1',
            'anonymous_entries.*.daftar_pelanggaran_id' => 'required|exists:daftar_pelanggaran,id',
            'asrama_id' => 'required|exists:asrama,id',
            'daftar_pelanggaran_id' => 'required_without_all:santri_pelanggaran,anonymous_entries|exists:daftar_pelanggaran,id',
            'petugas_id' => 'nullable|exists:petugas,id',
            'jumlah' => 'nullable|integer|min:1',
            'sumber_pencatatan' => 'required|in:petugas,ketua_kamar',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
        ];

        return $rules;
    }
}
