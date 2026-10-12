<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'quantity' => 'required|integer|min:1|max:999',
        ];
    }

    public function messages()
    {
        return [
            'quantity.required' => 'Jumlah wajib diisi.',
            'quantity.integer'  => 'Jumlah harus berupa angka.',
            'quantity.min'      => 'Quantity minimal 1.',
            'quantity.max'      => 'Quantity maksimal 999.',
        ];
    }
}
