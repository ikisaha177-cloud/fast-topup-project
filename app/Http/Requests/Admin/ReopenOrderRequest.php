<?php

namespace App\Http\Requests\Admin;

use App\Support\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Validasi aksi "buka kembali" pesanan yang sudah diputuskan gagal/dibatalkan.
 *
 * Hanya boleh kembali ke `pending` — supaya admin memperbaiki salah klik,
 * bukan mengubah hasil menjadi status lain secara diam-diam.
 */
class ReopenOrderRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('manage-catalog');
    }

    public function rules()
    {
        return [
            // `nullable`: form wajib boleh mengirim state apa pun selama tujuannya
            // tetap `pending`; field bersifat deklaratif, bukan prasyarat.
            'status' => ['nullable', Rule::in([OrderStatus::PENDING])],
        ];
    }

    public function messages()
    {
        return [
            'status.in' => 'Pesanan hanya dapat dibuka kembali menjadi Menunggu Konfirmasi.',
        ];
    }
}
