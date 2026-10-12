<?php

namespace App\Http\Requests\Admin;

use App\Support\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateStatusRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('manage-catalog');
    }

    public function rules()
    {
        return [
            'status'         => ['required', Rule::in(OrderStatus::adminDecisions())],
            'failure_reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            'status.required'    => 'Status wajib dipilih.',
            'status.in'          => 'Admin hanya dapat menyetujui atau menolak pesanan.',
            'failure_reason.max' => 'Alasan gagal maksimal 255 karakter.',
        ];
    }
}
