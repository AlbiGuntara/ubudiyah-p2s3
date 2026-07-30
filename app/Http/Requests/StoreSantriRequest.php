<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSantriRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->isMethod('POST')) {
            return $this->user()->can('create_santri');
        }
        return $this->user()->can('edit_santri');
    }

    public function rules(): array
    {
        return [
            'nama' => 'required|string|max:100',
            'nis' => 'nullable|string|max:50',
            'iksass' => 'nullable|string|max:20',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'nama_panggilan' => 'nullable|string|max:50',
            'asrama_id' => 'required|exists:asrama,id',
        ];
    }
}
