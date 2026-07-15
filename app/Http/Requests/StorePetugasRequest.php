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
        return [
            'santri_id' => 'required|exists:santri,id',
            'asrama_id' => 'nullable|exists:asrama,id',
            'jabatan' => 'required|string|max:100',
            'tugas' => 'required|string|max:100',

        ];
    }
}
