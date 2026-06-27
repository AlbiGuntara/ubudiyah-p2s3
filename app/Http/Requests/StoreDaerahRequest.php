<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDaerahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kode' => [
                'required',
                'string',
                'max:20',
                Rule::unique('daerah', 'kode')
                    ->ignore($this->route('daerah'))
                    ->whereNull('deleted_at'),
            ],
            'nama_daerah' => 'required|string|max:100',
        ];
    }
}
