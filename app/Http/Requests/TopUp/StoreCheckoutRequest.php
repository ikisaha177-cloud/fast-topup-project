<?php

namespace App\Http\Requests\TopUp;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'game_slug'    => 'required|string|exists:game_titles,slug',
            'user_game_id' => 'required|string|max:50',
            'server_id'    => 'nullable|string|max:50',
            'product_id'   => 'required|integer|exists:products,id',
        ];
    }

    public function messages()
    {
        return [
            'game_slug.required'    => 'Game tidak dikenali.',
            'game_slug.exists'      => 'Game tidak ditemukan.',
            'user_game_id.required' => 'User ID wajib diisi.',
            'user_game_id.max'      => 'User ID maksimal 50 karakter.',
            'server_id.max'         => 'Server ID maksimal 50 karakter.',
            'product_id.required'   => 'Pilih nominal top up terlebih dahulu.',
            'product_id.exists'     => 'Produk tidak ditemukan atau sudah nonaktif.',
        ];
    }
}
