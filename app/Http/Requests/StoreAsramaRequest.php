<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAsramaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'daerah_id' => 'required|exists:daerah,id',
            'nomor' => [
                'required',
                'string',
                'max:20',
                Rule::unique('asrama', 'nomor')
                    ->where('daerah_id', $this->daerah_id)
                    ->whereNull('deleted_at')
                    ->ignore($this->route('asrama')),
            ],
        ];
    }
}
