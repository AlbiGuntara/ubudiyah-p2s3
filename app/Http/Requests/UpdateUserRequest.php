<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_users');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($this->route('user'))],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'password' => 'nullable|string|min:8',
            'role' => 'required|string|in:super_admin,petugas,pembina',
        ];
    }
}
