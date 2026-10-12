<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameTitle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Game hanya boleh dihapus bila belum punya transaksi, supaya riwayat
     * pembayaran (audit) tidak ikut terhapus oleh cascade.
     */
    public function hasTransactions()
    {
        return $this->transactions()->exists();
    }
}
