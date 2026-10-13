<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PromoRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('manage-catalog');
    }

    /**
     * Cek unik & validasi format dijalankan di atas kode yang sudah dinormalkan,
     * supaya "hemat10" tidak lolos validasi lalu tabrakan dengan "HEMAT10" saat disimpan.
     */
    protected function prepareForValidation()
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
    }

    public function rules()
    {
        // Nilai persentase maksimal 100; nilai nominal (fixed) butuh ruang lebih luas.
        $valueMax = $this->input('type') === 'percent' ? 100 : 100000000;

        return [
            'code'         => [
                'required', 'string', 'min:3', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('promo_codes', 'code')->ignore($this->route('id')),
            ],
            'description'  => 'nullable|string|max:255',
            'type'         => ['required', 'string', Rule::in(['percent', 'fixed'])],
            'value'        => ['required', 'integer', 'min:1', 'max:' . $valueMax],
            'min_spend'    => 'nullable|integer|min:0',
            'max_discount' => 'nullable|integer|min:0',
            'usage_limit'  => 'nullable|integer|min:1',
            'scope'        => ['required', 'string', Rule::in(['all', 'topup', 'marketplace'])],
            'is_active'    => 'nullable|boolean',
            'starts_at'    => 'nullable|date',
            'ends_at'      => 'nullable|date',
        ];
    }

    public function messages()
    {
        return [
            'code.required'  => 'Kode promo wajib diisi.',
            'code.min'       => 'Kode promo minimal 3 karakter.',
            'code.max'       => 'Kode promo maksimal 40 karakter.',
            'code.regex'     => 'Kode promo hanya boleh berisi huruf, angka, tanda minus, atau garis bawah.',
            'code.unique'    => 'Kode promo ini sudah dipakai.',
            'type.in'        => 'Tipe promo tidak valid.',
            'value.required' => 'Nilai promo wajib diisi.',
            'value.min'      => 'Nilai promo minimal 1.',
            'value.max'      => 'Nilai persentase tidak boleh melebihi 100.',
            'usage_limit.min'=> 'Batas pemakaian minimal 1.',
            'scope.in'       => 'Cakupan promo tidak valid.',
            'ends_at.date'   => 'Waktu berakhir tidak valid.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $starts = $this->input('starts_at');
            $ends   = $this->input('ends_at');

            // Dilewati bila waktu mulai kosong: promo tanpa jadwal mulai dianggap
            // langsung berlaku sampai waktu berakhir.
            if ($starts && $ends && strtotime($ends) < strtotime($starts)) {
                $validator->errors()->add('ends_at', 'Waktu berakhir harus setelah waktu mulai.');
            }
        });
    }

    /**
     * Data siap simpan.
     *
     * @return array<string, mixed>
     */
    public function payload()
    {
        return [
            'code'         => $this->input('code'),
            'description'  => $this->input('description'),
            'type'         => $this->input('type'),
            'value'        => (int) $this->input('value'),
            'min_spend'    => (int) $this->input('min_spend', 0),
            'max_discount' => $this->filled('max_discount') ? (int) $this->input('max_discount') : null,
            'usage_limit'  => $this->filled('usage_limit') ? (int) $this->input('usage_limit') : null,
            'scope'        => $this->input('scope'),
            'is_active'    => $this->boolean('is_active'),
            'starts_at'    => $this->input('starts_at'),
            'ends_at'      => $this->input('ends_at'),
        ];
    }
}
