<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ProductRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('manage-catalog');
    }

    public function rules()
    {
        return [
            'game_title_id' => 'required|integer|exists:game_titles,id',
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string|max:1000',
            'price'         => 'required|integer|min:0|max:100000000',
            'stock'         => 'required|integer|min:0|max:1000000',
            'is_active'     => 'nullable|boolean',
        ];
    }

    public function messages()
    {
        return [
            'game_title_id.required' => 'Pilih game terlebih dahulu.',
            'game_title_id.exists'   => 'Game tidak ditemukan.',
            'name.required'          => 'Nama produk wajib diisi.',
            'price.required'         => 'Harga wajib diisi.',
            'price.integer'          => 'Harga harus berupa angka.',
            'price.max'              => 'Harga terlalu besar.',
            'stock.required'         => 'Stok wajib diisi.',
            'stock.max'              => 'Stok terlalu besar.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload()
    {
        return [
            'game_title_id' => (int) $this->input('game_title_id'),
            'name'          => $this->input('name'),
            'description'   => $this->input('description'),
            'price'         => (int) $this->input('price'),
            'stock'         => (int) $this->input('stock'),
            'is_active'     => $this->boolean('is_active'),
        ];
    }
}
