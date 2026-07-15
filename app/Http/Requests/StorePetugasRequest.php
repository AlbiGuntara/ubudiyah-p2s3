<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePetugasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
