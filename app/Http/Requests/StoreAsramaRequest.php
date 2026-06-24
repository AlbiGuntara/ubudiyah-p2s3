<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'nomor' => 'required|string|max:20',
        ];
    }
}
