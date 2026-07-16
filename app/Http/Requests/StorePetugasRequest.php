<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePetugasRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->isMethod('POST')) {
            return $this->user()->can('create_petugas');
        }
        return $this->user()->can('edit_petugas');
    }

    public function rules(): array
    {
        $rules = [
            'nama' => 'required|string|max:100',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'daerah_id' => 'nullable|exists:daerah,id',
            'asrama_id' => 'nullable|exists:asrama,id',
            'jabatan' => 'required|string|max:100',
            'tugas' => 'required|string|max:100',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['foto'] = 'nullable|image|mimes:jpeg,png,jpg|max:2048';
        }

        return $rules;
    }
}
