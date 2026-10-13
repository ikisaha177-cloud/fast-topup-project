# GantinoID Store

<p align="center">
  <img src="public/images/logo-horizontal.svg" alt="GantinoID Store — Digital Top-Up &amp; Marketplace" width="430">
</p>

Aplikasi web **top up game** dan **marketplace item game** berbasis Laravel 8. Pembeli bisa top up nominal game (Mobile Legends, Free Fire, PUBG Mobile, Valorant) dan membeli produk marketplace (voucher, skin, akun, koin). Admin mengelola katalog, memantau transaksi, memverifikasi pembayaran, dan melihat log aktivitas.

## Fitur

### Pembeli
- Top up game: pilih game → isi User ID / Server ID → pilih nominal → bayar.
- Katalog game dengan **pencarian + filter kategori** (konsisten dengan marketplace).
- Marketplace: katalog dengan pencarian, filter kategori, dan pagination.
- **Harga coret & badge diskon** pada produk marketplace (kolom `compare_at_price`), jadi diskon tampil sebagai potongan eksplisit, bukan sekadar angka.
- **Kode promo** di checkout top up & marketplace: masukkan kode → diskon langsung dihitung dan ditampilkan sebagai baris terpisah (harga item tetap harga katalog). Mendukung diskon **persen** (dengan plafon opsional) dan **nominal**, syarat **minimal belanja**, **batas pemakaian**, **masa berlaku**, serta **cakupan** (`all` / hanya top up / hanya marketplace). Kode tidak peka huruf besar-kecil. Aturan validasi hidup di satu tempat (`App\Services\PromoService`), dipakai ulang oleh checkout, halaman sukses, invoice, struk, dan riwayat.
- **Batas waktu pembayaran yang benar-benar berlaku**: setiap pesanan baru punya jendela bayar (`PAYMENT_WINDOW_MINUTES`, default **3 jam**). Halaman sukses & portal menampilkan **hitungan mundur** yang jujur; begitu waktunya habis, pesanan dibatalkan otomatis, **stok dikembalikan**, dan alasan "kedaluwarsa" tercatat apa adanya. Pembatalan terjadi **saat halamannya diakses** (lazy expiry — tanpa cron) lewat `TransactionService`/`MarketplaceOrderService`, jadi tidak ada penjadwal yang perlu dijalankan. Pesanan yang pembelinya sudah menandai "Saya Sudah Bayar" **tidak** ikut kedaluwarsa: itu keputusan admin di antrean verifikasi, bukan keputusan jam.
- **Ulasan & rating dari pembelian terverifikasi** (marketplace): angka muncul **hanya dari ulasan nyata** — pembeli dengan pesanan **Selesai** bisa menulis 1–5 bintang plus komentar dari halaman produk; server memvalidasi ulang bahwa pesanan miliknya, statusnya Selesai, dan produknya memang ada di pesanan itu (bukti pembelian tersimpan di `marketplace_order_id`). Kunci unik `(user, produk)` membuat kiriman kedua berfungsi sebagai **perbarui**, jadi rata-rata rating selalu dihitung dari pembeli yang berbeda — tidak bisa digelembungkan lewat pembelian berulang. **Produk tanpa ulasan jujur menulis "Belum ada ulasan"** (bukan "0.0"), penulis ditampilkan menyamar ("Budi S."), dan ulasan otomatis ikut terhapus bila admin **membatalkan konfirmasi** pesanannya — dasar klaim "Pembelian terverifikasi" ikut hilang bersama pesanannya.
- Keranjang (session) dengan validasi stok di server.
- **Alur konfirmasi pesanan**: pembeli memesan → menandai **"Saya Sudah Bayar"** → status **Menunggu Konfirmasi**; admin memverifikasi lalu menandai **Selesai** atau **Gagal** (stok otomatis dikembalikan). Pembeli juga bisa **membatalkan** pesanan selama masih menunggu konfirmasi. Untuk pembayaran digital, admin bisa lewat jalur otomatis — lihat *Alur Status Pesanan* di bawah.
- **Unggah bukti pembayaran (opsional)**: saat menandai sudah bayar, pembeli boleh melampirkan gambar bukti transfer (jpg/png/webp, maks 2 MB). Bukti tampil di halaman status pembeli dan panel verifikasi admin, serta bisa disusulkan selama pesanan masih menunggu konfirmasi.
- **Halaman invoice siap cetak** (`/invoice/{kode}`) untuk top up & marketplace: kop, rincian item, QR, total, dan cap **LUNAS**/**Menunggu Konfirmasi**; bisa disimpan sebagai PDF dan dijadikan screenshot bukti pembayaran. Tautan tersedia di halaman sukses, detail pembeli, dan panel admin.
- **Portal pembayaran terpisah** (`/payment/{kode}`): tombol **"Bayar Sekarang di Portal Pembayaran"** pada halaman sukses mengalihkan pembeli ke halaman berdiri sendiri (layout `gateway`, tanpa navbar/footer toko, terasa seperti web lain) berisi instruksi bayar sesuai metode — **QRIS** (kode QR), **transfer bank** (nomor Virtual Account), atau **e-wallet** (nomor tujuan). Nomor akun dibuat **deterministik** dari kode transaksi (crc32) tanpa panggilan jaringan dan selalu dilabeli simulasi. Setelah menekan **"Saya Sudah Bayar"**, halaman berubah menjadi **struk** (dokumen kertas putih dengan cap status) yang bisa di-screenshot, dicetak, atau disimpan PDF — lalu diunggah di halaman pesanan sebagai bukti. Portal **tidak** mengambil alih keputusan verifikasi: ia memakai service yang sama dengan tombol di toko, sehingga aturan `manual`/`hybrid`/`auto` tetap berlaku. Pesanan `failed`/`cancelled` otomatis tampil sebagai "tidak dapat dibayar" tanpa tombol bayar.
- **Kebijakan verifikasi terkonfigurasi** (`PAYMENT_VERIFICATION`): `hybrid` (default — ada bukti → otomatis Selesai, tanpa bukti → menunggu admin), `manual` (selalu admin), atau `auto` (setiap klaim langsung Selesai). Lihat *Alur Status Pesanan*.
- **Gateway pembayaran pluggable**: `simulation` (default, tanpa jaringan) atau **Midtrans Snap** — cukup ubah `PAYMENT_DRIVER` di `.env`.
- **Webhook pembayaran** dengan verifikasi signature + **idempotency** (event duplikat dari gateway tidak diproses dua kali).
- Halaman status pesanan dengan kode QR (simulasi), instruksi, dan tombol aksi yang menyesuaikan keadaan (belum bayar / menunggu verifikasi / selesai / gagal + alasan). Tombol utama **"Bayar Sekarang di Portal Pembayaran"** mengarah ke portal terpisah; bila gateway mengembalikan URL pembayaran eksternal, tombol "Buka Halaman Pembayaran" tetap muncul otomatis.
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
   │
   └────(batas waktu habis)─────────▶ cancelled  (stok dikembalikan, otomatis)

failed / cancelled ──(admin buka kembali)──▶ pending  (salah klik diperbaiki; stok dikunci ulang; jendela bayar dimulai ulang)
```

Ada **tiga jalur menuju `success`**, dan mana yang dipakai dicatat jujur di kolom `confirmation_source`:

- **`admin`** — verifikasi manual. Dipakai untuk pembayaran yang dicek manusia (transfer/QRIS tanpa gateway). Mengisi `confirmed_by`, `confirmed_at`, dan `failure_reason` bila ditolak.
- **`gateway`** — webhook pembayaran (Midtrans/simulasi) menandai sukses otomatis. `confirmed_by` dibiarkan `NULL` karena tidak ada admin yang memutuskan; UI menampilkan **"Otomatis (gateway pembayaran)"**.
- **`auto`** — sistem menyelesaikan pesanan dari penandaan/bukti pembeli sesuai **kebijakan verifikasi** (`App\Support\PaymentVerification`): mode `hybrid` (bukti → otomatis) atau `auto` (klaim apa pun → otomatis). `confirmed_by` juga `NULL`; UI menampilkan **"Otomatis (bukti pembayaran)"**. Mode `manual` mematikan jalur ini sepenuhnya.

Dengan driver default `simulation` dan mode default `hybrid`, pesanan tanpa bukti menunggu verifikasi admin, sedangkan pesanan **dengan bukti pembayaran** langsung selesai otomatis. Saat `PAYMENT_DRIVER=midtrans`, pesanan yang pembayarannya sudah *settlement* langsung selesai lewat jalur `gateway`.

- `failed` dan `cancelled` bersifat **terminal** lewat aksi biasa. Admin punya dua jalur perbaikan salah klik: **Buka Kembali** (pada `failed`/`cancelled` → `pending`, mengunci ulang stok) dan **Batalkan Konfirmasi** (pada `success` → `failed`, mengembalikan stok). Keduanya mencatat jejak audit terpisah (`revoked_at`/`revoked_by`) sehingga informasi konfirmasi awal tetap utuh.
- **Kedaluwarsa pembayaran** juga berakhir di `cancelled`, tapi jejaknya tidak menyamar sebagai keputusan manusia: `confirmation_source = expiry` dan `failure_reason` menyebut durasi nyatanya (mis. *"Pembayaran tidak diterima dalam batas waktu 3 jam."*). Pembeli diberi tahu lewat notifikasi email. Karena `cancelled` bisa dibuka kembali, admin yang salah membiarkan pesanan mati tetap bisa menghidupkannya — dan **jendela bayarnya dimulai ulang**, bukan diwarisi batas waktu lama yang sudah basi.
- Admin hanya punya dua keputusan di UI saat memverifikasi: **Konfirmasi** (success) dan **Gagal** (failed). Pembatalan adalah tindakan pembeli, sehingga validasi admin sengaja dibatasi ke dua nilai itu (`OrderStatus::adminDecisions()`).

### Navigasi & Penempatan Fitur
- Navbar pembeli ringkas: **Home · Top Up · Marketplace**, ikon **keranjang** dengan badge jumlah, dan **menu akun** (dropdown) berisi Riwayat Transaksi, Pesanan Saya, Profil, serta Keluar.
- Navbar admin: tautan **Lihat Toko** (buka tab baru) + menu akun berisi Dashboard, Verifikasi Pembayaran, Pesanan Masuk, Laporan Penjualan, dan Keluar. Navigasi antar-halaman admin tetap tersedia lewat sub-nav di dalam panel.
- Sub-navigasi akun (`partials/account-tabs`) menyatukan Riwayat/Pesanan/Profil sebagai satu section.
- Dropdown dapat diakses keyboard (klik untuk buka, `Esc` untuk tutup) dan otomatis menumpuk pada tampilan mobile.

### Admin
- Dashboard: KPI, grafik pendapatan 7 hari, transaksi & pesanan terbaru, serta penanda **Perlu Diverifikasi**.
- **Konfirmasi pesanan**: tombol **Konfirmasi** / **Gagal** (+ alasan opsional) langsung pada baris transaksi/pesanan yang masih menunggu; baris yang sudah ditandai "Sudah Bayar" disorot, dan baris dengan **bukti pembayaran** diberi badge "Bukti". Antrean ini otomatis **dibersihkan dari pesanan kedaluwarsa** saat dibuka, jadi yang tampil benar-benar masih menunggu keputusan — bukan pesanan yang sudah mati.
- **Detail transaksi & pesanan** (`/admin/transactions/{id}`, `/admin/orders/{id}`): data pembeli, item, jejak verifikasi (tandai bayar / pembayaran diterima / diverifikasi / konfirmasi dibatalkan), pratinjau **bukti pembayaran**, riwayat aktivitas per objek, dan panel verifikasi berisi tombol Konfirmasi/Gagal — termasuk **Buka Kembali** (salah klik pada gagal/batal) dan **Batalkan Konfirmasi** (salah konfirmasi `success` → `failed`). Saat membuka kembali, status kembali `pending`, seluruh jejak verifikasi dibersihkan, **jendela pembayaran dimulai ulang** (bukan mewarisi batas waktu yang sudah basi), dan stok dikunci ulang (dibatalkan bila stok sudah habis, supaya tidak pernah minus); saat membatalkan konfirmasi, stok dikembalikan dan jejak konfirmasi awal tetap utuh. Panel ini juga menampilkan baris **Batas Bayar** agar admin tahu sisa waktu pembeli.
- **Laporan harian / bulanan / tahunan**: ringkasan KPI, grafik pendapatan, tabel rincian per periode, sebaran status, game & produk terlaris (hanya pesanan selesai), breakdown metode pembayaran, filter status, dan **ekspor CSV**.
- **Kelola kode promo** (`/admin/promos`): buat, ubah, aktif/nonaktifkan (toggle), dan hapus kode beserta deskripsi, tipe diskon, minimal belanja, plafon, batas pemakaian, masa berlaku, dan cakupan. Kode yang **sudah pernah dipakai** dijaga: hanya bisa dinonaktifkan, tidak bisa dihapus, supaya riwayat transaksi tetap utuh.
- CRUD game (dengan upload icon) dan produk top up.
- Kelola produk marketplace (upload gambar/edit/nonaktifkan/hapus), termasuk harga jual & harga coret.
- Pantau transaksi & pesanan: pencarian, filter status, pagination, ubah status manual.
- **Log aktivitas** (audit trail) untuk setiap aksi penting admin.
- Penghapusan dijaga: game/produk yang sudah punya transaksi tidak bisa dihapus (riwayat tetap utuh).

### Tampilan
- **Design system gelap** (`public/css/style.css`) bergaya toko top up modern: permukaan gelap berlapis, kartu dengan garis tepi halus, aksen **kuning neon**, dan sampul game sebagai fokus utama. Dokumen yang memang harus terbaca di atas kertas (invoice, struk portal) tetap putih agar siap dicetak.
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

### Batas waktu pembayaran

```bash
# .env
PAYMENT_WINDOW_MINUTES=180   # 3 jam (default)
```

Berapa lama pembeli punya waktu untuk membayar sebelum pesanan `pending` dibatalkan otomatis dan
stoknya dikembalikan. Semua hitungan memakai satu sumber (`App\Support\PaymentWindow`), sehingga
hitungan mundur di UI dan keputusan "sudah kedaluwarsa" tidak pernah berbeda.

Nilai yang tidak masuk akal dijepit otomatis (minimal **5 menit**, maksimal **7 hari**) supaya salah
set `.env` tidak pernah mengunci stok selamanya atau membatalkan pesanan saat pembeli masih menekan
tombol bayar.

Tidak perlu cron atau penjadwal apa pun: pembatalan bersifat **lazy** — dijalankan saat pesanannya
diakses (halaman sukses, portal pembayaran, invoice, riwayat pembeli, dan daftar/detail admin).
Baris lama yang dibuat sebelum kolom `expires_at` ada dibiarkan **tanpa batas waktu**, jadi tidak
pernah ikut kedaluwarsa.

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

### Kode Promo Demo

| Kode       | Diskon      | Syarat            |
|------------|-------------|-------------------|
| `WELCOME15`  | 15% (maks Rp 50.000) | min. belanja Rp 20.000 |
| `HEMAT5000`  | Rp 5.000    | min. belanja Rp 30.000 |

Dibuat oleh `PromoCodeSeeder` (ikut `php artisan migrate --seed`). Kelola di `/admin/promos`.

## Testing

```bash
php vendor/bin/phpunit
```

Test memakai SQLite in-memory (`phpunit.xml`), jadi tidak butuh MySQL berjalan. Saat ini **293 test / 1200 assertion** hijau.

**Ulasan terverifikasi** (24 test): ulasan hanya bisa dibuat oleh pembeli pesanan **Selesai** yang produknya memang ada di pesanan itu (guest, pembeli lain, admin, produk di luar pesanan, serta status pending/gagal/dibatalkan ditolak), rating wajib 1–5, komentar opsional dan maksimal 1000 karakter, kiriman kedua **memperbarui** (bukan menggandakan), agregat dihitung dari pembeli berbeda, produk tanpa ulasan menampilkan **"Belum ada ulasan"** tanpa `ratingValue` di JSON-LD / tanpa bintang terisi / tanpa "0,0", halaman produk menampilkan komentar + nama penyamaran ("Budi S.") + badge pembelian terverifikasi, komentar `<script>` di-escape, `aggregateRating` baru muncul setelah ada ulasan, formulir hanya tampil bagi pembeli yang berhak (dan terisi ulang setelah mengulas), pintu masuk di halaman detail pesanan muncul/hilang sesuai status, pembatalan konfirmasi **menghapus ulasan pesanan itu** saja (ulasan pesanan lain utuh), nama penulis disamarkan, dan aktivitas `review.create`/`review.update` tercatat.

**Batas waktu pembayaran** (23 test): pesanan baru selalu dapat jendela bayar (`expires_at`) di kedua alur checkout, pembatalan otomatis saat halaman diakses berikut pengembalian stok (top up & marketplace), invoice/portal/riwayat menampilkan status sebenarnya setelah kedaluwarsa, daftar admin menyapu pesanan basi sementara riwayat pembeli hanya menyapu miliknya sendiri, klaim setelah batas waktu ditolak, jejak `confirmation_source = expiry` + alasan + notifikasi email, pesanan yang sudah ditandai "sudah bayar" **tidak** ikut kedaluwarsa, baris tanpa `expires_at` tidak pernah kedaluwarsa, idempotensi, pembatalan manual sebelum batas waktu tidak dicap kedaluwarsa, buka ulang memberi jendela baru, hitungan mundur tampil/sembunyi di tempat yang tepat, serta penjepitan nilai `PAYMENT_WINDOW_MINUTES`.

**Kode promo** (30 test): semua aturan validasi (kedaluwarsa, belum dimulai, dipakai semua, di bawah minimal belanja, salah cakupan, nonaktif), diskon persen dengan plafon & diskon nominal, penetapan kode di session (khusus checkout, terhapus otomatis saat tak berlaku, habis saat pesanan dibuat), alur lengkap pembuatan transaksi dengan `promo_code` + `discount_amount` + `amount` bersih, `used_count` naik dalam transaksi, kode terpakai tidak bisa dihapus, cakupan top up vs marketplace, CRUD + toggle admin (otentikasi & anti-duplikat peka huruf besar-kecil), serta render diskon di halaman sukses, invoice, struk portal, dan riwayat pesanan.

Mencakup alur checkout, konfirmasi pesanan oleh admin (berhasil/gagal + pengembalian stok + sumber konfirmasi), **portal pembayaran & struk** (instruksi QRIS/Virtual Account/e-wallet deterministik, konfirmasi idempoten, struk setelah konfirmasi, penolakan struk pada pesanan yang ditolak admin, alur lengkap portal → unggah bukti → Selesai, serta otorisasi guest/pemilik lain/admin), **unggah bukti pembayaran** (dengan/tanpa bukti, lengkapi susulan, tolak non-gambar, buka ulang menghapus berkas), **kebijakan verifikasi pembayaran** (`manual`/`hybrid`/`auto`: auto-konfirmasi dari bukti + jejak `confirmation_source=auto` tanpa `confirmed_by`, stok tetap terkunci, berlaku di top up & marketplace), **halaman invoice** (render untuk pemilik/guest top up, otorisasi marketplace, cap LUNAS vs instruksi bayar), **pembatalan konfirmasi `success`** oleh admin (stok kembali + jejak audit tanpa menimpa konfirmasi awal), halaman detail pesanan admin & aksi buka kembali (re-lock stok), **timeline verifikasi vertikal** pada detail transaksi/pesanan, **halaman kebijakan & sitemap.xml** (render, silang tautan, valid XML, tanpa kebocoran halaman privat), laporan harian/bulanan/tahunan & ekspor CSV, reservasi & pengembalian stok, notifikasi & queue, reset password, CRUD + audit log admin, otorisasi policy, webhook pembayaran (signature & idempotency), pencarian & filter katalog, serta **kualitas tampilan katalog & aksesibilitas** (gambar rasio tetap + atribut dimensi, tidak ada rating/testimoni karangan, kartu hero jujur — guest melihat pratinjau, pengguna melihat pesanan nyata berstatus apa adanya, `sold_count` hanya dari pesanan selesai, thumbnail di keranjang & riwayat pesanan, label sumber verifikasi, meta SEO, header tabel ber-`scope`, kolom pencarian ber-`aria-label`, penanda `aria-current` pada navigasi/tab/chip aktif, dan label langkah progres yang tidak menyesatkan).

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
app/Support/                  # Helper (status pesanan, metode bayar, jendela pembayaran, audit log)
config/payment.php            # Konfigurasi gateway pembayaran + jendela pembayaran
database/migrations/          # Skema database
database/seeders/             # Data awal
deploy/                       # Contoh konfigurasi Supervisor
resources/views/              # Blade templates
resources/views/pages/        # Halaman statis (kebijakan) + sitemap
resources/views/partials/     # Komponen bersama (ikon, tab akun, timeline, gambar produk)
resources/views/payment/      # Portal pembayaran (instruksi bayar + struk)
resources/views/layouts/      # Layout app (toko) & gateway (portal pembayaran)
public/css/style.css          # Design system
routes/web.php                # Route aplikasi
routes/webhooks.php           # Route webhook (stateless)
```

## Catatan
- Pembayaran default **simulasi**; set `PAYMENT_DRIVER=midtrans` + kredensial untuk memakai Midtrans Snap.
- Email memakai driver dari `.env` (default `mailhog` untuk dev). Ganti ke SMTP provider untuk produksi.
- Jalankan `php artisan queue:work` bila `QUEUE_CONNECTION=database` agar email notifikasi terkirim.
- `.env` default menunjuk ke MySQL. Untuk sekadar mencoba tanpa MySQL, set `DB_CONNECTION=sqlite` dan `DB_DATABASE=:memory:`.
