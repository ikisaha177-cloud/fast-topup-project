# TopUpMarket

Aplikasi web **top up game** dan **marketplace item game** berbasis Laravel 8. Pembeli bisa top up nominal game (Mobile Legends, Free Fire, PUBG Mobile, Valorant) dan membeli produk marketplace (voucher, skin, akun, koin). Admin mengelola katalog, memantau transaksi, memverifikasi pembayaran, dan melihat log aktivitas.

## Fitur

### Pembeli
- Top up game: pilih game → isi User ID / Server ID → pilih nominal → bayar.
- Marketplace: katalog dengan pencarian, filter kategori, dan pagination.
- Keranjang (session) dengan validasi stok di server.
- Pembayaran simulasi: `pending` → `success` (konfirmasi) atau `cancelled` (batal, stok dikembalikan).
- Halaman pembayaran dengan kode QR (simulasi), instruksi, dan tombol konfirmasi/batal.
- Riwayat transaksi top up dan pesanan marketplace, lengkap dengan filter status.
- Profil: ubah nama/password, statistik belanja.
- **Notifikasi email** untuk status pembayaran (top up & marketplace) dan email selamat datang.
- **Lupa password**: reset password lewat email.

### Admin
- Dashboard: KPI, grafik pendapatan 7 hari, transaksi & pesanan terbaru.
- CRUD game (dengan upload icon) dan produk top up.
- Kelola produk marketplace (upload gambar/edit/nonaktifkan/hapus).
- Pantau transaksi & pesanan: pencarian, filter status, pagination, ubah status manual.
- **Log aktivitas** (audit trail) untuk setiap aksi penting admin.

## Teknologi
- PHP 8 / Laravel 8
- MySQL (migrations + seeders); SQLite untuk testing
- Blade + CSS custom (tanpa framework CSS)
- Session-based cart
- Queue (sync) untuk email notifikasi

## Instalasi

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

> Untuk upload gambar (icon game / gambar produk), jalankan `php artisan storage:link` agar folder `storage/app/public` dapat diakses publik.
>
> Jika `storage:link` gagal di Windows/OneDrive (`Access is denied`), buat junction manual:
> `cmd /c "mklink /J public\storage storage\app\public"`.
>
> File `db_topup_market.sql` di root hanya **snapshot** lama. Sumber kebenaran skema adalah `database/migrations/` — selalu pakai `php artisan migrate --seed`.

## Akun Demo

| Peran   | Email                    | Password |
|---------|--------------------------|----------|
| Admin   | admin123@gmail.com       | admin123 |
| Pembeli | user@topupmarket.test    | password |
| Penjual | seller1@topupmarket.test | password |
| Penjual | seller2@topupmarket.test | password |

## Testing

```bash
php vendor/bin/phpunit
```

Test memakai SQLite in-memory (`phpunit.xml`), jadi tidak butuh MySQL berjalan. Saat ini **38 test / 159 assertion** hijau, mencakup alur checkout, reservasi & pengembalian stok, notifikasi, reset password, dan aksi admin.

## CI

Workflow GitHub Actions (`.github/workflows/tests.yml`) menjalankan seluruh test di setiap push / pull request.

## Struktur Utama

```
app/Http/Controllers/         # Logika pembeli
app/Http/Controllers/Admin/   # Logika admin
app/Http/Requests/            # Validasi terpusat (FormRequest)
app/Models/                   # Eloquent models
app/Notifications/            # Email notifikasi
app/Services/                 # Logika transisi status + stok
app/Support/                  # Helper (metode bayar, audit log)
database/migrations/          # Skema database
database/seeders/             # Data awal
resources/views/              # Blade templates
public/css/style.css          # Design system
routes/web.php                # Semua route
```

## Catatan
- Pembayaran masih **simulasi** (tanpa gateway). Integrasikan Midtrans/Xendit untuk produksi.
- Email memakai driver dari `.env` (default `mailhog` untuk dev). Ganti ke SMTP provider untuk produksi.
- `.env` default menunjuk ke MySQL. Untuk sekadar mencoba tanpa MySQL, set `DB_CONNECTION=sqlite` dan `DB_DATABASE=:memory:`.
