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

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        $rules = [
            'santri_id' => 'nullable|exists:santri,id',
            'santri_pelanggaran' => 'nullable|array',
            'santri_pelanggaran.*.santri_id' => 'required|exists:santri,id',
            'santri_pelanggaran.*.daftar_pelanggaran_id' => 'required|exists:daftar_pelanggaran,id',
            'santri_pelanggaran.*.tanggal' => 'required|date',
            'anonymous_entries' => 'nullable|array',
            'anonymous_entries.*.jumlah' => 'required|integer|min:1',
            'anonymous_entries.*.daftar_pelanggaran_id' => 'required|exists:daftar_pelanggaran,id',
            'anonymous_entries.*.tanggal' => 'required|date',

            // Asrama, sumber, dan keterangan boleh diisi per entri. Ini
            // dipakai tabel pencatatan voice, yang bisa memuat beberapa asrama
            // sekaligus. Kalau tidak dikirim, nilai tingkat form yang dipakai,
            // sehingga pencatatan manual tidak berubah.
            'santri_pelanggaran.*.asrama_id' => 'nullable|exists:asrama,id',
            'santri_pelanggaran.*.sumber_pencatatan' => 'nullable|in:petugas,ketua_kamar',
            'santri_pelanggaran.*.keterangan' => 'nullable|string',
            'anonymous_entries.*.asrama_id' => 'nullable|exists:asrama,id',
            'anonymous_entries.*.sumber_pencatatan' => 'nullable|in:petugas,ketua_kamar',
            'anonymous_entries.*.keterangan' => 'nullable|string',

            'asrama_id' => 'required|exists:asrama,id',
            'daftar_pelanggaran_id' => 'required_without_all:santri_pelanggaran,anonymous_entries|exists:daftar_pelanggaran,id',
            'petugas_id' => 'nullable|exists:petugas,id',
            'jumlah' => 'nullable|integer|min:1',
            'sumber_pencatatan' => 'required|in:petugas,ketua_kamar',
            'keterangan' => 'nullable|string',
        ];

        if ($this->isMethod('PUT')) {
            $rules['tanggal'] = 'required|date';
            $rules['asrama_id'] = 'sometimes|exists:asrama,id';
        }

        return $rules;
    }
}
