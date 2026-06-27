<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'nis' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('santri', 'nis')
                    ->ignore($this->route('santri'))
                    ->whereNull('deleted_at'),
            ],
            'iksass' => 'nullable|string|max:20',
            'asrama_id' => 'required|exists:asrama,id',
        ];
    }
}
