<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'product_id' => 'required|integer',
            'quantity'   => 'required|integer|min:1|max:999',
        ];
    }

    public function messages()
    {
        return [
            'product_id.required' => 'Produk tidak dikenali.',
            'quantity.required'   => 'Jumlah wajib diisi.',
            'quantity.min'        => 'Quantity minimal 1.',
            'quantity.max'        => 'Quantity maksimal 999.',
        ];
    }
}
