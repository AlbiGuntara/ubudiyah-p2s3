<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSantriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama' => 'required|string|max:100',
            'nis' => 'nullable|string|max:50|unique:santri,nis,' . $this->route('santri'),
            'iksass' => 'nullable|string|max:20',
            'asrama_id' => 'required|exists:asrama,id',
        ];
    }
}
