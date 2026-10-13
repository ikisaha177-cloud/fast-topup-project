<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\HandlesImageUpload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class MarketplaceProductRequest extends FormRequest
{
    use HandlesImageUpload;

    public function authorize()
    {
        return Gate::allows('manage-catalog');
    }

    public function rules()
    {
        return [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'price'       => 'required|integer|min:0|max:100000000',
            'compare_at_price' => 'nullable|integer|min:0|max:100000000|gt:price',
            'stock'       => 'required|integer|min:0|max:1000000',
            'category'    => 'nullable|string|max:255',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:2048',
            'is_active'   => 'nullable|boolean',
        ];
    }

    public function messages()
    {
        return [
            'name.required'  => 'Nama produk wajib diisi.',
            'price.required' => 'Harga wajib diisi.',
            'price.max'      => 'Harga terlalu besar.',
            'compare_at_price.gt' => 'Harga coret harus lebih besar dari harga jual.',
            'compare_at_price.max' => 'Harga coret terlalu besar.',
            'stock.required' => 'Stok wajib diisi.',
            'stock.max'      => 'Stok terlalu besar.',
            'image.image'    => 'Gambar harus berupa file gambar.',
            'image.mimes'    => 'Format gambar harus jpg, jpeg, png, webp, atau svg.',
            'image.max'      => 'Ukuran gambar maksimal 2 MB.',
        ];
    }

    /**
     * @param  \App\Models\MarketplaceProduct|null  $product
     * @return array<string, mixed>
     */
    public function payload($product = null)
    {
        $compareAt = $this->input('compare_at_price');

        return [
            'name'        => $this->input('name'),
            'description' => $this->input('description'),
            'price'       => (int) $this->input('price'),
            'compare_at_price' => ($compareAt === null || $compareAt === '') ? null : (int) $compareAt,
            'stock'       => (int) $this->input('stock'),
            'category'    => $this->input('category'),
            'image'       => $this->storeImage('image', 'images/marketplace', $product->image ?? null),
            'is_active'   => $this->boolean('is_active'),
        ];
    }
}
