<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePembinaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'santri_id' => 'required|exists:santri,id',
            'panggilan' => 'required|in:I,II,III',
            'tanggal_panggilan' => 'required|date',
            'sanksi' => 'nullable|string',
        ];
    }
}
