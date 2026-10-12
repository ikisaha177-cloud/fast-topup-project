# TopUpMarket

Aplikasi web **top up game** dan **marketplace item game** berbasis Laravel 8. Pembeli bisa top up nominal game (Mobile Legends, Free Fire, PUBG Mobile, Valorant) dan membeli produk marketplace (voucher, skin, akun, koin). Admin mengelola katalog, memantau transaksi, memverifikasi pembayaran, dan melihat log aktivitas.

## Fitur

### Pembeli
- Top up game: pilih game → isi User ID / Server ID → pilih nominal → bayar.
- Katalog game dengan **pencarian + filter kategori** (konsisten dengan marketplace).
- Marketplace: katalog dengan pencarian, filter kategori, dan pagination.
- Keranjang (session) dengan validasi stok di server.
- Pembayaran simulasi: `pending` → `success` (konfirmasi) atau `cancelled` (batal, stok dikembalikan).
- **Gateway pembayaran pluggable**: `simulation` (default, tanpa jaringan) atau **Midtrans Snap** — cukup ubah `PAYMENT_DRIVER` di `.env`.
- **Webhook pembayaran** dengan verifikasi signature + **idempotency** (event duplikat dari gateway tidak diproses dua kali).
- Halaman pembayaran dengan kode QR (simulasi), instruksi, dan tombol konfirmasi/batal. Bila gateway mengembalikan URL pembayaran, tombol "Buka Halaman Pembayaran" muncul otomatis.
- Area **Akun Saya** dengan sub-navigasi tab: Riwayat Top Up, Pesanan Saya, dan Profil.
- Riwayat transaksi top up dan pesanan marketplace, lengkap dengan filter status.
- Profil: ubah nama/password, statistik belanja.
- **Notifikasi email** untuk status pembayaran (top up & marketplace) dan email selamat datang — dikirim lewat **antrean (queue)** agar tidak memblokir request.
- **Lupa password**: reset password lewat email.

### Navigasi & Penempatan Fitur
- Navbar pembeli ringkas: **Home · Top Up · Marketplace**, ikon **keranjang** dengan badge jumlah, dan **menu akun** (dropdown) berisi Riwayat Transaksi, Pesanan Saya, Profil, serta Keluar.
- Navbar admin: tautan **Lihat Toko** (buka tab baru) + menu akun berisi Dashboard, Verifikasi Pembayaran, Pesanan Masuk, dan Keluar. Navigasi antar-halaman admin tetap tersedia lewat sub-nav di dalam panel.
- Sub-navigasi akun (`partials/account-tabs`) menyatukan Riwayat/Pesanan/Profil sebagai satu section.
- Dropdown dapat diakses keyboard (klik untuk buka, `Esc` untuk tutup) dan otomatis menumpuk pada tampilan mobile.

### Admin
- Dashboard: KPI, grafik pendapatan 7 hari, transaksi & pesanan terbaru.
- CRUD game (dengan upload icon) dan produk top up.
- Kelola produk marketplace (upload gambar/edit/nonaktifkan/hapus).
- Pantau transaksi & pesanan: pencarian, filter status, pagination, ubah status manual.
- **Log aktivitas** (audit trail) untuk setiap aksi penting admin.
- Penghapusan dijaga: game/produk yang sudah punya transaksi tidak bisa dihapus (riwayat tetap utuh).

### Keamanan
- **Policy-based authorization** (`TransactionPolicy`, `MarketplaceOrderPolicy`) + Gate `manage-catalog` untuk area admin.
- Middleware `admin` / `not-admin` memisahkan area pembeli dan admin.
- Validasi terpusat lewat FormRequest; webhook diverifikasi dengan signature.

## Teknologi
- PHP 8 / Laravel 8
- MySQL (migrations + seeders); SQLite untuk testing
- Blade + CSS custom (tanpa framework CSS)
- Session-based cart
- Queue (database) untuk email notifikasi
- Midtrans Snap (opsional) untuk pembayaran

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

### Queue worker (email)

Notifikasi email dikirim lewat antrean supaya request user tidak menunggu proses kirim.
Default `.env.example` memakai `QUEUE_CONNECTION=database`, jadi jalankan worker:

```bash
php artisan queue:work --tries=3
```

Untuk produksi, jaga worker tetap hidup dengan Supervisor (contoh: `deploy/supervisor-topupmarket.conf`).
Jika tidak ingin menjalankan worker, set `QUEUE_CONNECTION=sync` di `.env` (email dikirim langsung, request sedikit lebih lambat).

### Payment gateway (opsional)

```bash
# .env
PAYMENT_DRIVER=midtrans
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxx
MIDTRANS_IS_PRODUCTION=false
```

Daftarkan URL notifikasi di dashboard Midtrans:

```
https://domain-kamu.test/payment/webhook
```

Webhook diverifikasi dengan signature (`sha512(order_id + status_code + gross_amount + server_key)`) dan
diproses **idempotent**: event yang sama dikirim berulang hanya diproses sekali (dicatat di tabel
`payment_webhook_events`).

### SMTP

Untuk produksi ganti `MAIL_*` di `.env` ke SMTP provider (Mailtrap/Mailgun/Postmark/Gmail). Lihat contoh di `.env.example`.

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

Test memakai SQLite in-memory (`phpunit.xml`), jadi tidak butuh MySQL berjalan. Saat ini **103 test / 384 assertion** hijau, mencakup alur checkout, reservasi & pengembalian stok, notifikasi & queue, reset password, CRUD + audit log admin, otorisasi policy, webhook pembayaran (signature & idempotency), serta pencarian & filter katalog.

## CI

Workflow GitHub Actions (`.github/workflows/tests.yml`) menjalankan seluruh test di setiap push / pull request.

## Struktur Utama

```
app/Http/Controllers/         # Logika pembeli
app/Http/Controllers/Admin/   # Logika admin
app/Http/Requests/            # Validasi terpusat (FormRequest)
app/Models/                   # Eloquent models
app/Notifications/            # Email notifikasi (queue)
app/Policies/                 # Otorisasi berbasis policy
app/Services/                 # Logika transisi status + stok
app/Services/Payment/         # Abstraksi gateway + processor webhook
app/Support/                  # Helper (metode bayar, audit log)
config/payment.php            # Konfigurasi gateway pembayaran
database/migrations/          # Skema database
database/seeders/             # Data awal
deploy/                       # Contoh konfigurasi Supervisor
resources/views/              # Blade templates
resources/views/partials/     # Komponen bersama (ikon, tab akun, gambar produk)
public/css/style.css          # Design system
routes/web.php                # Route aplikasi
routes/webhooks.php           # Route webhook (stateless)
```

## Catatan
- Pembayaran default **simulasi**; set `PAYMENT_DRIVER=midtrans` + kredensial untuk memakai Midtrans Snap.
- Email memakai driver dari `.env` (default `mailhog` untuk dev). Ganti ke SMTP provider untuk produksi.
- Jalankan `php artisan queue:work` bila `QUEUE_CONNECTION=database` agar email notifikasi terkirim.
- `.env` default menunjuk ke MySQL. Untuk sekadar mencoba tanpa MySQL, set `DB_CONNECTION=sqlite` dan `DB_DATABASE=:memory:`.
