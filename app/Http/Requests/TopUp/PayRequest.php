<?php

namespace App\Http\Requests\TopUp;

use App\Support\PaymentMethods;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'payment_method' => ['required', 'string', Rule::in(PaymentMethods::all())],
        ];
    }

    public function messages()
    {
        return [
            'payment_method.required' => 'Pilih metode pembayaran.',
            'payment_method.in'       => 'Metode pembayaran tidak valid.',
        ];
    }
}
