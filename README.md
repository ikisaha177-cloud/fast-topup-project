# GantinoID Store

Aplikasi web **top up game** dan **marketplace item game** berbasis Laravel 8. Pembeli bisa top up nominal game (Mobile Legends, Free Fire, PUBG Mobile, Valorant) dan membeli produk marketplace (voucher, skin, akun, koin). Admin mengelola katalog, memantau transaksi, memverifikasi pembayaran, dan melihat log aktivitas.

## Fitur

### Pembeli
- Top up game: pilih game → isi User ID / Server ID → pilih nominal → bayar.
- Katalog game dengan **pencarian + filter kategori** (konsisten dengan marketplace).
- Marketplace: katalog dengan pencarian, filter kategori, dan pagination.
- **Harga coret & badge diskon** pada produk marketplace (kolom `compare_at_price`), jadi diskon tampil sebagai potongan eksplisit, bukan sekadar angka.
- Keranjang (session) dengan validasi stok di server.
- **Alur konfirmasi pesanan**: pembeli memesan → menandai **"Saya Sudah Bayar"** → status **Menunggu Konfirmasi**; admin memverifikasi lalu menandai **Selesai** atau **Gagal** (stok otomatis dikembalikan). Pembeli juga bisa **membatalkan** pesanan selama masih menunggu konfirmasi. Untuk pembayaran digital, admin bisa lewat jalur otomatis — lihat *Alur Status Pesanan* di bawah.
- **Unggah bukti pembayaran (opsional)**: saat menandai sudah bayar, pembeli boleh melampirkan gambar bukti transfer (jpg/png/webp, maks 2 MB). Bukti tampil di halaman status pembeli dan panel verifikasi admin, serta bisa disusulkan selama pesanan masih menunggu konfirmasi.
- **Halaman invoice siap cetak** (`/invoice/{kode}`) untuk top up & marketplace: kop, rincian item, QR, total, dan cap **LUNAS**/**Menunggu Konfirmasi**; bisa disimpan sebagai PDF dan dijadikan screenshot bukti pembayaran. Tautan tersedia di halaman sukses, detail pembeli, dan panel admin.
- **Kebijakan verifikasi terkonfigurasi** (`PAYMENT_VERIFICATION`): `hybrid` (default — ada bukti → otomatis Selesai, tanpa bukti → menunggu admin), `manual` (selalu admin), atau `auto` (setiap klaim langsung Selesai). Lihat *Alur Status Pesanan*.
- **Gateway pembayaran pluggable**: `simulation` (default, tanpa jaringan) atau **Midtrans Snap** — cukup ubah `PAYMENT_DRIVER` di `.env`.
- **Webhook pembayaran** dengan verifikasi signature + **idempotency** (event duplikat dari gateway tidak diproses dua kali).
- Halaman status pesanan dengan kode QR (simulasi), instruksi, dan tombol aksi yang menyesuaikan keadaan (belum bayar / menunggu verifikasi / selesai / gagal + alasan). Bila gateway mengembalikan URL pembayaran, tombol "Buka Halaman Pembayaran" muncul otomatis.
- Area **Akun Saya** dengan sub-navigasi tab: Riwayat Top Up, Pesanan Saya, dan Profil.
- Riwayat transaksi top up dan pesanan marketplace, lengkap dengan filter status.
- **Detail transaksi/pesanan diperkaya**: timeline verifikasi vertikal yang menampilkan tiap tahap (pesanan dibuat → tandai bayar → unggah bukti → verifikasi admin/gateway → hasil akhir), termasuk jejak bila konfirmasi pernah dibatalkan admin.
- Profil: ubah nama/password, statistik belanja.
- **Notifikasi email** untuk status pembayaran (top up & marketplace) dan email selamat datang — dikirim lewat **antrean (queue)** agar tidak memblokir request.
- **Lupa password**: reset password lewat email.

### Alur Status Pesanan

Status dijaga satu pintu di `App\Support\OrderStatus` + `TransactionService` / `MarketplaceOrderService`, sehingga stok, jejak verifikasi, dan notifikasi selalu konsisten.

```
pending ──(admin setujui)───────────▶ success    (pesanan selesai)
   │                                    │
   │                                    └──(admin batalkan konfirmasi)──▶ failed  (stok dikembalikan)
   │
   ├────(webhook gateway sukses)────▶ success    (pembayaran digital terverifikasi otomatis)
   │
   ├────(admin tolak)───────────────▶ failed     (gagal, stok dikembalikan)
   │
   └────(pembeli batal)─────────────▶ cancelled  (stok dikembalikan)

failed / cancelled ──(admin buka kembali)──▶ pending  (salah klik diperbaiki; stok dikunci ulang)
```

Ada **tiga jalur menuju `success`**, dan mana yang dipakai dicatat jujur di kolom `confirmation_source`:

- **`admin`** — verifikasi manual. Dipakai untuk pembayaran yang dicek manusia (transfer/QRIS tanpa gateway). Mengisi `confirmed_by`, `confirmed_at`, dan `failure_reason` bila ditolak.
- **`gateway`** — webhook pembayaran (Midtrans/simulasi) menandai sukses otomatis. `confirmed_by` dibiarkan `NULL` karena tidak ada admin yang memutuskan; UI menampilkan **"Otomatis (gateway pembayaran)"**.
- **`auto`** — sistem menyelesaikan pesanan dari penandaan/bukti pembeli sesuai **kebijakan verifikasi** (`App\Support\PaymentVerification`): mode `hybrid` (bukti → otomatis) atau `auto` (klaim apa pun → otomatis). `confirmed_by` juga `NULL`; UI menampilkan **"Otomatis (bukti pembayaran)"**. Mode `manual` mematikan jalur ini sepenuhnya.

Dengan driver default `simulation` dan mode default `hybrid`, pesanan tanpa bukti menunggu verifikasi admin, sedangkan pesanan **dengan bukti pembayaran** langsung selesai otomatis. Saat `PAYMENT_DRIVER=midtrans`, pesanan yang pembayarannya sudah *settlement* langsung selesai lewat jalur `gateway`.

- `failed` dan `cancelled` bersifat **terminal** lewat aksi biasa. Admin punya dua jalur perbaikan salah klik: **Buka Kembali** (pada `failed`/`cancelled` → `pending`, mengunci ulang stok) dan **Batalkan Konfirmasi** (pada `success` → `failed`, mengembalikan stok). Keduanya mencatat jejak audit terpisah (`revoked_at`/`revoked_by`) sehingga informasi konfirmasi awal tetap utuh.
- Admin hanya punya dua keputusan di UI saat memverifikasi: **Konfirmasi** (success) dan **Gagal** (failed). Pembatalan adalah tindakan pembeli, sehingga validasi admin sengaja dibatasi ke dua nilai itu (`OrderStatus::adminDecisions()`).

### Navigasi & Penempatan Fitur
- Navbar pembeli ringkas: **Home · Top Up · Marketplace**, ikon **keranjang** dengan badge jumlah, dan **menu akun** (dropdown) berisi Riwayat Transaksi, Pesanan Saya, Profil, serta Keluar.
- Navbar admin: tautan **Lihat Toko** (buka tab baru) + menu akun berisi Dashboard, Verifikasi Pembayaran, Pesanan Masuk, Laporan Penjualan, dan Keluar. Navigasi antar-halaman admin tetap tersedia lewat sub-nav di dalam panel.
- Sub-navigasi akun (`partials/account-tabs`) menyatukan Riwayat/Pesanan/Profil sebagai satu section.
- Dropdown dapat diakses keyboard (klik untuk buka, `Esc` untuk tutup) dan otomatis menumpuk pada tampilan mobile.

### Admin
- Dashboard: KPI, grafik pendapatan 7 hari, transaksi & pesanan terbaru, serta penanda **Perlu Diverifikasi**.
- **Konfirmasi pesanan**: tombol **Konfirmasi** / **Gagal** (+ alasan opsional) langsung pada baris transaksi/pesanan yang masih menunggu; baris yang sudah ditandai "Sudah Bayar" disorot, dan baris dengan **bukti pembayaran** diberi badge "Bukti".
- **Detail transaksi & pesanan** (`/admin/transactions/{id}`, `/admin/orders/{id}`): data pembeli, item, jejak verifikasi (tandai bayar / pembayaran diterima / diverifikasi / konfirmasi dibatalkan), pratinjau **bukti pembayaran**, riwayat aktivitas per objek, dan panel verifikasi berisi tombol Konfirmasi/Gagal — termasuk **Buka Kembali** (salah klik pada gagal/batal) dan **Batalkan Konfirmasi** (salah konfirmasi `success` → `failed`). Saat membuka kembali, status kembali `pending`, seluruh jejak verifikasi dibersihkan, dan stok dikunci ulang (dibatalkan bila stok sudah habis, supaya tidak pernah minus); saat membatalkan konfirmasi, stok dikembalikan dan jejak konfirmasi awal tetap utuh.
- **Laporan harian / bulanan / tahunan**: ringkasan KPI, grafik pendapatan, tabel rincian per periode, sebaran status, game & produk terlaris (hanya pesanan selesai), breakdown metode pembayaran, filter status, dan **ekspor CSV**.
- CRUD game (dengan upload icon) dan produk top up.
- Kelola produk marketplace (upload gambar/edit/nonaktifkan/hapus), termasuk harga jual & harga coret.
- Pantau transaksi & pesanan: pencarian, filter status, pagination, ubah status manual.
- **Log aktivitas** (audit trail) untuk setiap aksi penting admin.
- Penghapusan dijaga: game/produk yang sudah punya transaksi tidak bisa dihapus (riwayat tetap utuh).

### Tampilan
- **Design system terang & bersih** (`public/css/style.css`) bergaya toko top up modern: permukaan putih, kartu bersih, aksen emas/oranye, dan sampul game sebagai fokus utama.
- Ikon SVG inline (tanpa font emoji) agar tampil konsisten di semua OS.
- **Standar gambar 8:5**: gambar produk & logo game selalu dibungkus kotak rasio tetap (`aspect-ratio: 8 / 5`) dengan atribut `width`/`height`, sehingga tidak ada layout shift dan tinggi kartu konsisten di semua breakpoint. Foto produk memakai `object-fit: cover` (crop rapi); logo game memakai `object-fit: contain` supaya brand mark tidak pernah terpotong.
- Thumbnail ringkas (`is-thumb`, 54×54) dan logo mini (`game-logo-xs`) dipakai konsisten di keranjang, checkout, riwayat pesanan, halaman sukses, serta tabel admin — jadi item selalu punya identitas visual.
- **Konten jujur, tanpa social proof karangan**: tidak ada rating palsu, testimoni fiktif, klaim "100% penjual terverifikasi", atau estimasi durasi proses. Angka yang tampil diambil dari data nyata: jumlah produk & penjual aktif dari katalog, dan label **"Terjual N"** dihitung dari kuantitas item pada pesanan berstatus **Selesai** (`MarketplaceProduct::scopeWithSoldCount()` — anti N+1). Produk yang belum pernah terjual ditandai **"Belum terjual"**. Kartu melayang di hero pun jujur: pengunjung tanpa pesanan hanya melihat **"Pratinjau Pesanan"** berlabel *Contoh*, sedangkan pengguna yang login melihat **pesanan nyata terakhirnya** dengan status apa adanya (bukan selalu "Selesai").
- **Kartu produk tunggal** (`partials/marketplace-card`) dipakai di home, katalog, dan rekomendasi produk — satu sumber kebenaran untuk tata letak gambar, harga, dan label, sehingga tidak ada markup yang menyimpang antar halaman.
- **SEO & aksesibilitas dasar**: meta Open Graph + Twitter Card, data terstruktur JSON-LD (`Product` di detail produk dengan harga/ketersediaan, `WebSite` di home), favicon SVG bertema brand, `robots.txt` yang menutup area privat, **`/sitemap.xml`** yang dihasilkan otomatis dari katalog aktif (game & produk marketplace, tanpa membocorkan halaman privat), `theme-color`, **skip-link** ke konten utama, dan gaya fokus keyboard yang jelas (`:focus-visible`).
- **Halaman kebijakan & bantuan**: **Cara Order**, **Syarat & Ketentuan**, dan **Kebijakan Privasi** (route `/cara-order`, `/syarat-ketentuan`, `/kebijakan-privasi`) dengan navigasi tab bersama dan tautan di footer.
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

Untuk produksi, jaga worker tetap hidup dengan Supervisor (contoh: `deploy/supervisor-gantinoid.conf`).
Jika tidak ingin menjalankan worker, set `QUEUE_CONNECTION=sync` di `.env` (email dikirim langsung, request sedikit lebih lambat).

### Payment gateway (opsional)

```bash
# .env
PAYMENT_DRIVER=midtrans
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxx
MIDTRANS_IS_PRODUCTION=false
```

Kebijakan verifikasi pembayaran diatur terpisah lewat `PAYMENT_VERIFICATION` (`hybrid` default, `manual`, atau `auto`) — lihat *Alur Status Pesanan*.

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

Test memakai SQLite in-memory (`phpunit.xml`), jadi tidak butuh MySQL berjalan. Saat ini **196 test / 779 assertion** hijau, mencakup alur checkout, konfirmasi pesanan oleh admin (berhasil/gagal + pengembalian stok + sumber konfirmasi), **unggah bukti pembayaran** (dengan/tanpa bukti, lengkapi susulan, tolak non-gambar, buka ulang menghapus berkas), **kebijakan verifikasi pembayaran** (`manual`/`hybrid`/`auto`: auto-konfirmasi dari bukti + jejak `confirmation_source=auto` tanpa `confirmed_by`, stok tetap terkunci, berlaku di top up & marketplace), **halaman invoice** (render untuk pemilik/guest top up, otorisasi marketplace, cap LUNAS vs instruksi bayar), **pembatalan konfirmasi `success`** oleh admin (stok kembali + jejak audit tanpa menimpa konfirmasi awal), halaman detail pesanan admin & aksi buka kembali (re-lock stok), **timeline verifikasi vertikal** pada detail transaksi/pesanan, **halaman kebijakan & sitemap.xml** (render, silang tautan, valid XML, tanpa kebocoran halaman privat), laporan harian/bulanan/tahunan & ekspor CSV, reservasi & pengembalian stok, notifikasi & queue, reset password, CRUD + audit log admin, otorisasi policy, webhook pembayaran (signature & idempotency), pencarian & filter katalog, serta **kualitas tampilan katalog & aksesibilitas** (gambar rasio tetap + atribut dimensi, tidak ada rating/testimoni karangan, kartu hero jujur — guest melihat pratinjau, pengguna melihat pesanan nyata berstatus apa adanya, `sold_count` hanya dari pesanan selesai, thumbnail di keranjang & riwayat pesanan, label sumber verifikasi, meta SEO, header tabel ber-`scope`, kolom pencarian ber-`aria-label`, penanda `aria-current` pada navigasi/tab/chip aktif, dan label langkah progres yang tidak menyesatkan).

## CI

Workflow GitHub Actions (`.github/workflows/tests.yml`) menjalankan seluruh test di setiap push / pull request.

## Struktur Utama

```
app/Http/Controllers/         # Logika pembeli (termasuk halaman statis & sitemap)
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
resources/views/pages/        # Halaman statis (kebijakan) + sitemap
resources/views/partials/     # Komponen bersama (ikon, tab akun, timeline, gambar produk)
public/css/style.css          # Design system
routes/web.php                # Route aplikasi
routes/webhooks.php           # Route webhook (stateless)
```

## Catatan
- Pembayaran default **simulasi**; set `PAYMENT_DRIVER=midtrans` + kredensial untuk memakai Midtrans Snap.
- Email memakai driver dari `.env` (default `mailhog` untuk dev). Ganti ke SMTP provider untuk produksi.
- Jalankan `php artisan queue:work` bila `QUEUE_CONNECTION=database` agar email notifikasi terkirim.
- `.env` default menunjuk ke MySQL. Untuk sekadar mencoba tanpa MySQL, set `DB_CONNECTION=sqlite` dan `DB_DATABASE=:memory:`.
