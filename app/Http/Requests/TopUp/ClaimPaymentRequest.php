<?php

namespace App\Http\Requests\TopUp;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Pembeli menandai pembayaran sudah dilakukan, dengan bukti opsional.
 */
class ClaimPaymentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'payment_proof' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }

    public function messages()
    {
        return [
            'payment_proof.image' => 'Bukti pembayaran harus berupa gambar.',
            'payment_proof.mimes' => 'Format bukti harus JPG, PNG, atau WEBP.',
            'payment_proof.max'   => 'Ukuran bukti maksimal 2 MB.',
        ];
    }
}
