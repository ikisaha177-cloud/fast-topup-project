<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Trait untuk menangani upload gambar opsional.
 *
 * Field bisa diisi dengan:
 *  - file upload (UploadedFile)  -> disimpan ke disk `public`, path relatif dikembalikan
 *  - string path lama            -> dipertahankan apa adanya (backward compatible)
 *  - kosong                      -> path lama dipertahankan (saat edit) atau null (saat create)
 */
trait HandlesImageUpload
{
    /**
     * Simpan file gambar (kalau ada) dan kembalikan path relatifnya.
     *
     * @param  string       $field    nama input pada request
     * @param  string       $folder   folder tujuan di disk public (mis. images/games)
     * @param  string|null  $current  path lama yang dipakai saat tidak ada file baru
     * @return string|null
     */
    protected function storeImage($field, $folder, $current = null)
    {
        $file = $this->file($field);

        if ($file instanceof UploadedFile) {
            // Hapus file lama hanya jika file tersebut hasil upload aplikasi.
            if ($current && $this->isManagedPath($current)) {
                Storage::disk('public')->delete($current);
            }

            $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $name = $name !== '' ? $name : 'image';
            $filename = $name . '-' . Str::random(8) . '.' . $file->getClientOriginalExtension();

            return $file->storeAs($folder, $filename, 'public');
        }

        $value = $this->input($field);

        // String path manual dari admin tetap dipakai.
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        return $current;
    }

    /**
     * Cek apakah path ini file yang dikelola aplikasi (di storage public),
     * bukan aset statis di folder public/.
     */
    protected function isManagedPath($path)
    {
        return is_string($path)
            && Str::startsWith($path, ['images/games/', 'images/products/', 'images/marketplace/'])
            && ! file_exists(public_path($path));
    }
}
