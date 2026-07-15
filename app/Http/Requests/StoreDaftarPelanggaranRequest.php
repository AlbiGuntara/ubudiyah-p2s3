<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDaftarPelanggaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->isMethod('POST')) {
            return $this->user()->can('create_daftar_pelanggaran');
        }
        return $this->user()->can('edit_daftar_pelanggaran');
    }

    public function rules(): array
    {
        return [
            'nama_pelanggaran' => 'required|string|max:200',
        ];
    }
}
