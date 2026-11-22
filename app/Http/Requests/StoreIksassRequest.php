<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIksassRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->isMethod('POST')) {
            return $this->user()->can('create_iksass');
        }

        return $this->user()->can('edit_iksass');
    }

    public function rules(): array
    {
        return [
            'kode' => [
                'required',
                'string',
                'max:20',
                Rule::unique('iksass', 'kode')
                    ->ignore($this->route('iksass'))
                    ->whereNull('deleted_at'),
            ],
            'nama_iksass' => 'required|string|max:100',
        ];
    }
}
