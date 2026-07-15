<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePembinaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->isMethod('POST')) {
            return $this->user()->can('create_pembinaan');
        }
        return $this->user()->can('edit_pembinaan');
    }

    public function rules(): array
    {
        return [
            'santri_id' => 'required|exists:santri,id',
            'sanksi' => 'nullable|integer|min:0',
            'shalawat_tertulis' => 'nullable|integer|min:0',
        ];
    }
}