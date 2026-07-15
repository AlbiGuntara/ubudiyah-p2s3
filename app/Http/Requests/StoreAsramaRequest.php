<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAsramaRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->isMethod('POST')) {
            return $this->user()->can('create_asrama');
        }
        return $this->user()->can('edit_asrama');
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
