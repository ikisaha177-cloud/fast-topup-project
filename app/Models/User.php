<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Nama pengguna untuk halaman publik, disamarkan sebagian
     * ("Budi Santoso" → "Budi S.").
     *
     * Halaman publik boleh menampilkan siapa yang beraktivitas tanpa
     * membocorkan identitas lengkap.
     */
    public function publicName(): string
    {
        $name = trim((string) $this->name);

        if ($name === '') {
            return 'Pembeli';
        }

        $parts = preg_split('/\s+/', $name, 2);

        if (count($parts) < 2) {
            return Str::limit($parts[0], 18, '');
        }

        return $parts[0] . ' ' . Str::upper(Str::substr($parts[1], 0, 1)) . '.';
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function marketplaceProducts()
    {
        return $this->hasMany(MarketplaceProduct::class);
    }

    public function marketplaceOrders()
    {
        return $this->hasMany(MarketplaceOrder::class);
    }

    /**
     * Cek apakah user adalah admin.
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }
}
