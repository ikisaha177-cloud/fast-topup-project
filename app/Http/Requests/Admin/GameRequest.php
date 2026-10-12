<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\HandlesImageUpload;
use Illuminate\Foundation\Http\FormRequest;

class GameRequest extends FormRequest
{
    use HandlesImageUpload;

    public function authorize()
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules()
    {
        return [
            'name'      => 'required|string|max:255',
            'category'  => 'nullable|string|max:255',
            'icon'      => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:2048',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Nama game wajib diisi.',
            'name.max'      => 'Nama game maksimal 255 karakter.',
            'icon.image'    => 'Icon harus berupa file gambar.',
            'icon.mimes'    => 'Format icon harus jpg, jpeg, png, webp, atau svg.',
            'icon.max'      => 'Ukuran icon maksimal 2 MB.',
        ];
    }

    /**
     * Data siap simpan: slug unik + icon (upload / path lama).
     *
     * @param  \App\Models\GameTitle|null  $game
     * @return array<string, mixed>
     */
    public function payload($game = null)
    {
        return [
            'name'      => $this->input('name'),
            'category'  => $this->input('category'),
            'icon'      => $this->storeImage('icon', 'images/games', $game->icon ?? null),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
