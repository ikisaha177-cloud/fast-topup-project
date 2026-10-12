<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() !== null;
    }

    public function rules()
    {
        return [
            'name'             => 'required|string|max:255',
            'current_password' => 'required|string',
            'new_password'     => 'nullable|string|min:8|confirmed',
        ];
    }

    public function messages()
    {
        return [
            'name.required'             => 'Nama wajib diisi.',
            'current_password.required' => 'Password lama wajib diisi.',
            'new_password.min'          => 'Password baru minimal 8 karakter.',
            'new_password.confirmed'    => 'Konfirmasi password baru tidak sama.',
        ];
    }
}
