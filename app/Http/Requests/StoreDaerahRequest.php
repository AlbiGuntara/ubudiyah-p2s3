<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDaerahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kode' => 'required|string|max:20|unique:daerah,kode,' . $this->route('daerah'),
            'nama_daerah' => 'required|string|max:100',
        ];
    }
}
