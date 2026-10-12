# TopUpMarket

Aplikasi web **top up game** dan **marketplace item game** berbasis Laravel 8. Pembeli bisa top up nominal game (Mobile Legends, Free Fire, PUBG Mobile, Valorant) dan membeli produk marketplace (voucher, skin, akun, koin). Admin mengelola katalog, memantau transaksi, memverifikasi pembayaran, dan melihat log aktivitas.

## Fitur

### Pembeli
- Top up game: pilih game → isi User ID / Server ID → pilih nominal → bayar.
- Katalog game dengan **pencarian + filter kategori** (konsisten dengan marketplace).
- Marketplace: katalog dengan pencarian, filter kategori, dan pagination.
- **Harga coret & badge diskon** pada produk marketplace (kolom `compare_at_price`), jadi diskon tampil sebagai potongan eksplisit, bukan sekadar angka.
- Keranjang (session) dengan validasi stok di server.
- **Alur konfirmasi pesanan**: pembeli memesan → menandai **"Saya Sudah Bayar"** → status **Menunggu Konfirmasi**; admin memverifikasi lalu menandai **Selesai** atau **Gagal** (stok otomatis dikembalikan). Pembeli juga bisa **membatalkan** pesanan selama masih menunggu konfirmasi. Untuk pembayaran digital, admin bisa lewat jalur otomatis — lihat *Alur Status Pesanan* di bawah.
- **Gateway pembayaran pluggable**: `simulation` (default, tanpa jaringan) atau **Midtrans Snap** — cukup ubah `PAYMENT_DRIVER` di `.env`.
- **Webhook pembayaran** dengan verifikasi signature + **idempotency** (event duplikat dari gateway tidak diproses dua kali).
- Halaman status pesanan dengan kode QR (simulasi), instruksi, dan tombol aksi yang menyesuaikan keadaan (belum bayar / menunggu verifikasi / selesai / gagal + alasan). Bila gateway mengembalikan URL pembayaran, tombol "Buka Halaman Pembayaran" muncul otomatis.
- Area **Akun Saya** dengan sub-navigasi tab: Riwayat Top Up, Pesanan Saya, dan Profil.
- Riwayat transaksi top up dan pesanan marketplace, lengkap dengan filter status.
- Profil: ubah nama/password, statistik belanja.
- **Notifikasi email** untuk status pembayaran (top up & marketplace) dan email selamat datang — dikirim lewat **antrean (queue)** agar tidak memblokir request.
- **Lupa password**: reset password lewat email.

### Alur Status Pesanan

Status dijaga satu pintu di `App\Support\OrderStatus` + `TransactionService` / `MarketplaceOrderService`, sehingga stok, jejak verifikasi, dan notifikasi selalu konsisten.

```
pending ──(admin setujui)───────────▶ success    (pesanan selesai)
   │
   ├────(webhook gateway sukses)────▶ success    (pembayaran digital terverifikasi otomatis)
   │
   ├────(admin tolak)───────────────▶ failed     (gagal, stok dikembalikan)
   │
   └────(pembeli batal)─────────────▶ cancelled  (stok dikembalikan)
```

Ada **dua jalur menuju `success`**, dan mana yang dipakai dicatat jujur di kolom `confirmation_source`:

- **`admin`** — verifikasi manual. Dipakai untuk pembayaran yang dicek manusia (transfer/QRIS tanpa gateway). Mengisi `confirmed_by`, `confirmed_at`, dan `failure_reason` bila ditolak.
- **`gateway`** — webhook pembayaran (Midtrans/simulasi) menandai sukses otomatis. `confirmed_by` dibiarkan `NULL` karena tidak ada admin yang memutuskan; UI menampilkan **"Otomatis (gateway pembayaran)"**.

Dengan driver default `simulation`, tidak ada webhook yang datang sendiri, jadi seluruh pesanan melewati verifikasi admin. Saat `PAYMENT_DRIVER=midtrans`, pesanan yang pembayarannya sudah *settlement* langsung selesai tanpa perlu admin.

- `success`, `failed`, dan `cancelled` bersifat **terminal** — tidak bisa diubah lagi.
- Admin hanya punya dua keputusan di UI: **Konfirmasi** (success) dan **Gagal** (failed). Pembatalan adalah tindakan pembeli, sehingga validasi admin sengaja dibatasi ke dua nilai itu (`OrderStatus::adminDecisions()`).

### Navigasi & Penempatan Fitur
- Navbar pembeli ringkas: **Home · Top Up · Marketplace**, ikon **keranjang** dengan badge jumlah, dan **menu akun** (dropdown) berisi Riwayat Transaksi, Pesanan Saya, Profil, serta Keluar.
- Navbar admin: tautan **Lihat Toko** (buka tab baru) + menu akun berisi Dashboard, Verifikasi Pembayaran, Pesanan Masuk, Laporan Penjualan, dan Keluar. Navigasi antar-halaman admin tetap tersedia lewat sub-nav di dalam panel.
- Sub-navigasi akun (`partials/account-tabs`) menyatukan Riwayat/Pesanan/Profil sebagai satu section.
- Dropdown dapat diakses keyboard (klik untuk buka, `Esc` untuk tutup) dan otomatis menumpuk pada tampilan mobile.

### Admin
- Dashboard: KPI, grafik pendapatan 7 hari, transaksi & pesanan terbaru, serta penanda **Perlu Diverifikasi**.
- **Konfirmasi pesanan**: tombol **Konfirmasi** / **Gagal** (+ alasan opsional) langsung pada baris transaksi/pesanan yang masih menunggu; baris yang sudah ditandai "Sudah Bayar" disorot.
- **Laporan harian / bulanan / tahunan**: ringkasan KPI, grafik pendapatan, tabel rincian per periode, sebaran status, game & produk terlaris (hanya pesanan selesai), breakdown metode pembayaran, filter status, dan **ekspor CSV**.
- CRUD game (dengan upload icon) dan produk top up.
- Kelola produk marketplace (upload gambar/edit/nonaktifkan/hapus), termasuk harga jual & harga coret.
- Pantau transaksi & pesanan: pencarian, filter status, pagination, ubah status manual.
- **Log aktivitas** (audit trail) untuk setiap aksi penting admin.
- Penghapusan dijaga: game/produk yang sudah punya transaksi tidak bisa dihapus (riwayat tetap utuh).

### Tampilan
- **Design system terang & bersih** (`public/css/style.css`) bergaya toko top up modern: permukaan putih, kartu bersih, aksen emas/oranye, dan sampul game sebagai fokus utama.
- Ikon SVG inline (tanpa font emoji) agar tampil konsisten di semua OS.
- Animasi *reveal on scroll* sebagai progressive enhancement (halaman tetap tampil penuh bila JS mati).

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

Saat gateway aktif, webhook yang melaporkan pembayaran **sukses** (settlement/capture) langsung menandai
pesanan **Selesai** dengan `confirmation_source = gateway` — tanpa campur tangan admin. Webhook yang
melaporkan pembayaran **gagal/kedaluwarsa** menandai pesanan `failed`/`cancelled` dan mengembalikan stok.
Jadi admin hanya menangani pembayaran **manual** (mis. transfer yang perlu dicek manusia).

### SMTP

Untuk produksi ganti `MAIL_*` di `.env` ke SMTP provider (Mailtrap/Mailgun/Postmark/Gmail). Lihat contoh di `.env.example`.

## Akun Demo

Akun ini hanya untuk pengembangan/testing — **tidak ditampilkan di UI** agar halaman login tetap bersih.

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

Test memakai SQLite in-memory (`phpunit.xml`), jadi tidak butuh MySQL berjalan. Saat ini **127 test / 492 assertion** hijau, mencakup alur checkout, konfirmasi pesanan oleh admin (berhasil/gagal + pengembalian stok + sumber konfirmasi), laporan harian/bulanan/tahunan & ekspor CSV, reservasi & pengembalian stok, notifikasi & queue, reset password, CRUD + audit log admin, otorisasi policy, webhook pembayaran (signature & idempotency), serta pencarian & filter katalog.

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
app/Services/                 # Logika transisi status + stok + laporan
app/Services/Payment/         # Abstraksi gateway + processor webhook
app/Support/                  # Helper (status pesanan, metode bayar, audit log)
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
