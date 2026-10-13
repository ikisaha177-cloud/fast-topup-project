# Review System Takapedia & Gap Analysis vs GantinoID Store

> **Tanggal review:** 13 Oktober 2026
> **Reviewer:** internal (CodeBuddy)
> **Metode:** arsip Wayback Machine (capture 2023–2026), CDX URL inventory, analisis DOM/asset,
> dokumen invoice publik, dan hasil pencarian web. Situs live memblokir akses otomatis
> (Cloudflare challenge), sehingga analisis bersandar pada snapshot arsip + bukti aset.
> Semua temuan di bawah bisa diverifikasi ulang; yang berupa opini ditandai *(penilaian)*.

---

## 1. Ringkasan Eksekutif

Takapedia adalah platform top up game asal Indonesia yang memposisikan diri sebagai
*"Store Specialist Mobile Legends No.1 — Murah, Aman, Terpercaya, Legal 100%, Open 24 Jam"*.

**Tiga hal yang paling penting untuk kita ambil:**

1. **Katalog & kanal pembayaran jauh lebih luas dari kita.** ~190 halaman produk dan
   ~19 kanal bayar (11 VA bank, 5 e-wallet, QRIS, Alfamart, Indomaret). Kita punya 4 game
   contoh + marketplace, dan 5 label metode (masih simulasi).
2. **Mereka punya lapisan komersial yang belum kita miliki sama sekali:** kode promo,
   program afiliasi, saldo/mutasi, leaderboard, dan ulasan. Ini gap fungsi, bukan gap kosmetik.
3. **Mereka lebih lemah di area yang justru kita kuatkan:** kejujuran data, aksesibilitas,
   higiene data, dan ketertelusuran verifikasi. Ulasan mereka justru kosong/rusak.

**Kesimpulan:** Takapedia menang di *luas* (katalog, kanal bayar, fitur marketing),
kita menang di *dalam* (marketplace multi-penjual, audit verifikasi, kejujuran katalog, aksesibilitas).
Prioritas kita bukan meniru 190 game, tapi mengambil **mekanisme** yang terbukti
(kode promo, kedaluwarsa pesanan, ulasan terverifikasi) dengan kualitas yang lebih rapi.

---

## 2. Profil & Positioning

| Aspek | Takapedia |
|---|---|
| Kategori | Top up game, voucher, pulsa/data, token PLN, **jasa** (joki, sewa squad, gift skin) |
| Positioning | "Termurah" + "No.1 MLBB" + "Legal 100%" + "Open 24 Jam" |
| Target | Gamer mobile Indonesia (MLBB sebagai poros) |
| Model | **Single-store** (satu penjual: Takapedia sendiri) |
| Bahasa/valuta | Multi-bahasa (`/id-id`, `/en`, `/en-th`) + multi-valuta (ID/IDR) |
| Kanal dukungan | WhatsApp, Instagram, Discord, TikTok, YouTube, email |
| Konten | Subdomain blog terpisah (`news.takapedia.com`) |

**Catatan penting:** Takapedia juga menjual **jasa**, bukan hanya barang digital —
joki rank, joki MCL, video montage, sewa squad verified, room tournament, bahkan kelas
"Belajar Bareng". Ini kategori produk yang kita belum sentuh sama sekali.

---

## 3. Arsitektur & Tech Stack

### 3.1 Evolusi besar: Laravel → Next.js

Ini temuan paling struktural dari arsip:

| Era | Stack | Bukti |
|---|---|---|
| 2021–2023 | **Laravel + Bootstrap 4 + jQuery 3.5.1** | `assets/js/jquery-3.5.1.min.js`, `assets/vendor/bootstrap/*`, `assets/vendor/DataTables/1.10.22`, `assets/vendor/fontawesome`, `assets/vendor/alertifyjs`, `assets/scss/app.css`, `assets/admin/*` |
| 2024–sekarang | **Next.js (React, pages router)** | `_next/static/chunks/pages/_app-*.js`, `_buildManifest.js`, `_ssgManifest.js`, `/_next/image?url=…&w=&q=` |

Bukti lain era Next.js: font di-host sendiri (`_next/static/media/*.woff2`, pola `next/font`),
flag negara dari `react-circle-flags.pages.dev`, dan i18n berbasis path.

### 3.2 Infrastruktur

- **Cloudflare** di depan situs: bot challenge, **email protection** (`/cdn-cgi/l/email-protection`),
  Rocket Loader. Konsekuensinya: crawler dan pengguna tertentu diblokir (kami kena).
- **CDN sendiri:** `cdn.takapedia.com`.
- **Ketergantungan pihak ketiga:** sebagian aset dilayani dari `cdn.bangjeff.com` dan
  `client-cdn.bangjeff.com`, dan footer menautkan `venderpedia.com`.
  *(penilaian: mengindikasikan sebagian platform/inventaris berbagi infrastruktur dengan
  penyedia top-up lain — bukan dibangun 100% dari nol.)*
- **API pencarian:** endpoint `api/search` (terarsip sebagai 403 untuk crawler).
- **Blog terpisah:** `news.takapedia.com` — pemisahan konten marketing dari aplikasi transaksi.

### 3.3 Isu higiene yang terlihat di arsip

- Slug produk rusak/tidak konsisten: `snowbreak-:-containment-zone` (mengandung `:` di URL),
  `netfilx` (salah ketik Netflix), `goddes-of-victory-nikke` (seharusnya "Goddess"),
  `play-station-gc`.
- Aset vendor 404 di era lama: `assets/vendor/fontawesome/webfonts/*.ttf` dan `*.woff2` → **404**.
- Migrasi Next.js meninggalkan URL lama mati (`/order/{slug}`, `/status`, `/hitungwr`,
  `/hitungpointmw`) — menandakan churn SEO dan potensi kehilangan backlink.
- Redirect aneh dari bot/probe: `/%0A`, `/\n\n♠` → 301/404.

---

## 4. Peta Halaman (dari inventory URL)

```
/id-id                              Beranda
/id-id/{game-slug}                  Halaman produk  (~190 rute)
/id-id/invoices                     Cek transaksi + tabel "Transaksi Terakhir"
/id-id/leaderboard                  Leaderboard
/id-id/reviews                      Ulasan
/id-id/calculator/winrate           Kalkulator (alat gratis, mesin SEO)
/id-id/calculator/magic-wheel
/id-id/calculator/zodiac
/id-id/dashboard                    Area akun
/id-id/dashboard/history            Riwayat
/id-id/dashboard/mutation           Mutasi saldo
/id-id/dashboard/reload             Isi ulang saldo
/id-id/dashboard/report             Laporan
/id-id/dashboard/affiliate          Program afiliasi
/id-id/sign-in, /sign-up, /forgot-password
/id-id/contact-us
/id-id/privacy-policy, /terms-and-condition
```

**Yang layak dicatat:** adanya `dashboard/mutation` + `dashboard/reload` menandakan
**sistem saldo (wallet)** internal; `dashboard/affiliate` menandakan **program referral**;
`calculator/*` adalah **content marketing alat gratis** untuk menangkap trafik pencarian.

---

## 5. Alur Checkout & Pembayaran

Dari halaman produk (snapshot Jan 2026), alurnya **6 langkah dalam satu halaman**:

1. **Masukkan Data Akun** — ID, Server
2. **Pilih Nominal**
3. **Masukkan Jumlah Pembelian**
4. **Kode Promo** — dengan tombol "Gunakan" + "Pakai Promo Yang Tersedia"
5. **Pilih Pembayaran**
6. **Detail Kontak** — No. WhatsApp wajib, dengan peringatan eksplisit:
   *"Nomor ini akan dihubungi jika terjadi masalah"* dan *"Tulis tanpa angka 0 di depan"*

Lalu tombol **"Pesan Sekarang!"** → halaman invoice.

**Kedaluwarsa pembayaran:** dokumen invoice publik menunjukkan hitungan mundur
**2 jam 59 menit 22 detik** sebelum pesanan kedaluwarsa.

**Guest checkout diizinkan** — tidak wajib punya akun (sama seperti kita).

**Elemen kepercayaan di halaman produk:** badge animasi *Proses Cepat*, *Layanan Chat 24/7*,
*Pembayaran Aman*, plus panel "Jaminan Layanan": 24 Jam, Pembayaran Aman & Terpercaya,
Proses Cepat & Otomatis.

**Struktur halaman produk:** galeri/banner → panel order → **Deskripsi panjang** →
**Ulasan & rating** → **FAQ akordeon** (9 pertanyaan) → "Butuh Bantuan?" → footer raksasa.

---

## 6. Kanal Pembayaran (~19 kanal)

Dibuktikan dari nama berkas logo di arsip:

| Kelompok | Kanal |
|---|---|
| **VA Bank** (11) | BCA, BNI, BRI (BRIVA), Mandiri, CIMB, Permata, Maybank, Danamon, Sinarmas, BNC |
| **E-wallet** (5) | DANA, GoPay, OVO, ShopeePay, LinkAja |
| **QRIS** | QRIS — logo bertanda **`qris-ipaymu.png`** → gateway **iPaymu** |
| **Retail** (2) | Alfamart, Indomaret |
| **Voucher/GC** | Google Play, Steam, PlayStation, Roblox, Point Blank, Vidio, Fortnite V-Bucks, dll. |

**Pembanding kita:** `QRIS`, `Bank Transfer`, `DANA`, `GoPay`, `OVO` — dan semuanya masih
**simulasi** (nomor VA/e-wallet deterministik dari kode transaksi, tanpa jaringan).
Kita belum punya kanal **retail** (Alfamart/Indomaret) yang justru penting untuk
segmen yang tidak punya m-banking.

---

## 7. Trust & Social Proof — dan yang Rusak

### Yang mereka lakukan dengan benar
- **Feed transaksi nyata** di `/invoices`: tabel `Tanggal | Nomor Invoice | No. Handphone | Harga | Status`.
- **Ulasan dari pembeli nyata** di beranda, dengan identitas disamarkan
  (`081*****453`, `yuk*****@…`). Contoh nyata yang terarsip:
  *"mnatab lery cepat sekali langanan top up dimari"* — bahasa asli pengguna, bukan copywriter.
- **Rating hanya dari pembeli** (di halaman produk muncul *"Berdasarkan total 0 rating"*).

### Yang rusak *(temuan penting)*
- Halaman **`/id-id/reviews` kosong**: hanya tulisan *"Tidak Ada Lagi yang Dapat Dimuat"* (capture Okt 2025).
- Widget rating produk menampilkan **"0.0 / 5.0 — Dari 0 ulasan"** (capture Jan 2026),
  padahal copy-nya berbunyi *"Pelanggan merasa puas dengan produk ini."*
  → **klaim kepuasan tanpa satu pun ulasan.** Ini persis jenis klaim kosong yang kita hindari.
- Footer **keyword-stuffed**: satu paragraf ~1.000 karakter mengulang
  "Termurah / Aman / Terpercaya / Legal 100% / Open 24 Jam / Sahabat Para Gamers".
  Terlihat seperti spam SEO, dan dibaca manusia jadi melelahkan.

---

## 8. Kelebihan Takapedia

1. **Luas katalog** — ~190 produk, mencakup game mobile, PC, konsol, voucher, pulsa/data, token PLN.
2. **Lebar kanal bayar** — retail + VA lengkap menjangkau pengguna tanpa rekening bank.
3. **Mekanisme komersial matang** — kode promo, saldo, afiliasi, leaderboard.
4. **Konten & SEO** — blog terpisah, kalkulator gratis, deskripsi panjang per produk, FAQ per produk.
5. **Dukungan multi-kanal** — WhatsApp sebagai kanal utama (sangat relevan untuk pasar Indonesia).
6. **Social proof berbasis data nyata** (feed transaksi & ulasan asli) — arahnya benar.
7. **Halaman produk satu-halaman** — mengurangi friksi: pilih nominal, promo, bayar, isi kontak
   tanpa pindah halaman.

---

## 9. Kelemahan Takapedia

| # | Kelemahan | Bukti |
|---|---|---|
| 1 | Ulasan & rating praktis tidak berfungsi | `/reviews` kosong; produk "0 ulasan" tapi copy mengklaim kepuasan |
| 2 | Higiene data buruk | Slug `netfilx`, `goddes-of-victory-nikke`, `snowbreak-:-containment-zone` |
| 3 | Aksesibilitas lemah | Tautan ikon tanpa label: teks alt bocor sebagai `Iconly/Two-tone/Add UserDaftar` |
| 4 | Aset rusak | `assets/vendor/fontawesome/webfonts/*.ttf` → 404 |
| 5 | Churn URL saat migrasi | URL lama `/order/{slug}`, `/status`, `/hitungwr` mati setelah pindah ke Next.js |
| 6 | Blokir crawler agresif | Cloudflare challenge menghalangi crawler & pengguna sah |
| 7 | Footer spam kata kunci | ~1.000 karakter pengulangan klaim |
| 8 | Klaim pemasaran sulit diverifikasi | "Termurah", "No.1", "Legal 100%" tanpa bukti |
| 9 | Ketergantungan pihak ketiga | Aset dari `cdn.bangjeff.com` + tautan `venderpedia.com` |
| 10 | Tanpa multi-penjual | Model single-store: tidak ada ekosistem penjual/marketplace |

---

## 10. Gap Analysis: Takapedia vs GantinoID Store

### 10.1 Yang Takapedia punya, kita belum

| Fitur | Dampak | Bobot |
|---|---|---|
| **Kode promo / voucher** | Konversi & kampanye marketing | **Tinggi** |
| **Kedaluwarsa pembayaran** (+ auto-batal, stok kembali) | Melindungi stok & mempercepat keputusan | **Tinggi** |
| **Ulasan & rating dari pembelian terverifikasi** | Kepercayaan & SEO | **Tinggi** |
| **WhatsApp / live chat** sebagai kanal dukungan | Standar pasar Indonesia | **Tinggi** |
| **FAQ per produk** | SEO + mengurangi beban CS | Sedang |
| Kanal bayar retail (Alfamart/Indomaret) + VA bank luas | Menjangkau pengguna tanpa m-banking | Sedang |
| Feed transaksi terbaru (publik, disamarkan) | Social proof | Sedang |
| Kalkulator gratis (winrate, magic wheel, zodiac) | Akuisisi trafik organik | Sedang |
| Blog / konten terpisah | SEO jangka panjang | Sedang |
| Leaderboard | Gamifikasi & retensi | Rendah |
| Sistem saldo (mutation/reload) | Retensi & pembelian berulang | Sedang |
| Program afiliasi | Akuisisi | Sedang |
| Multi-bahasa / multi-valuta | Ekspansi | Rendah |
| Jasa (joki, sewa squad, gift skin) | Kategori produk baru | Sedang |

### 10.2 Yang kita punya, Takapedia tidak *(atau jauh lebih lemah)*

| Keunggulan kita | Nilai |
|---|---|
| **Marketplace multi-penjual** (stok, penjual, `sold_count` dari pesanan Selesai) | Ekosistem, bukan toko tunggal |
| **Kebijakan verifikasi terkonfigurasi** (`manual`/`hybrid`/`auto`) | Fleksibilitas operasional |
| **Jejak audit konfirmasi** (`admin`/`gateway`/`auto`, `confirmed_by`, revoke/reopen) | Akuntabilitas |
| **Unggah bukti pembayaran** | Menutup celah transfer manual |
| **Portal pembayaran + struk siap unggah** | Pengalaman bayar terpisah & bukti jelas |
| **Invoice kertas putih + cap LUNAS** | Dokumen siap cetak |
| **Katalog jujur** (tanpa rating/testimoni karangan; angka dari data nyata) | Kepercayaan jangka panjang |
| **Aksesibilitas** (skip-link, `aria-current`, `scope` tabel, `:focus-visible`) | Kepatuhan & UX |
| **Penguncian/pengembalian stok** yang dijaga | Anti stok minus |
| **Laporan + ekspor CSV** untuk admin | Operasional |
| **214 test otomatis** | Keandalan perubahan |
| **SEO teknis**: sitemap.xml, robots.txt, JSON-LD, OG/Twitter Card | Dasar yang bersih |

**Kesimpulan gap:** kita kalah di *jumlah fitur marketing*, menang di *kualitas rekayasa
dan kejujuran data*. Strategi paling masuk akal: **adopsi mekanisme komersial mereka
(kode promo, kedaluwarsa, ulasan terverifikasi) tanpa mengorbankan prinsip kejujuran kita.**

---

## 11. Roadmap Prioritas

### P0 — berdampak tinggi, sejalan dengan prinsip kita

1. **Kode promo / voucher**
   - Tabel `promo_codes` (tipe `percent`/`fixed`, `min_spend`, `max_discount`, kuota,
     rentang tanggal, cakupan top up/marketplace), admin CRUD, validasi terpusat.
   - Simpan `promo_code` + `discount_amount` pada transaksi & pesanan.
   - Tampilkan diskon di halaman sukses, invoice, portal struk, dan detail admin.

2. **Kedaluwarsa pembayaran + auto-batal (lazy expiry)**
   - Kolom `expires_at`; pesanan `pending` yang lewat batas dibatalkan otomatis
     **saat diakses** (tanpa cron), stok dikembalikan lewat jalur service yang sudah ada.
   - Hitungan mundur di portal & halaman sukses — jujur, karena benar-benar berlaku.

3. **Ulasan & rating dari pembelian terverifikasi**
   - Hanya pembeli dengan pesanan **Selesai** yang boleh mengulas (1 ulasan per pesanan).
   - Rating agregat dihitung dari ulasan nyata; **produk tanpa ulasan jujur menampilkan
     "Belum ada ulasan"** — bukan "0.0" palsu.

4. **Kanal dukungan WhatsApp + FAQ per produk**
   - Tombol WA dari konfigurasi (tanpa hardcode), FAQ akordeon per game (data terstruktur).

#### Status eksekusi

| # | Item | Status | Bukti |
|---|------|--------|-------|
| 1 | Kode promo / voucher | ✅ **selesai** | `promo_codes` + `PromoService` + admin CRUD + 30 test (`tests/Feature/PromoCodeTest.php`) |
| 2 | Kedaluwarsa pembayaran + auto-batal | ✅ **selesai** | kolom `expires_at`, `PaymentWindow`, `expireIfDue()`/`expireOverdue()`, jejak `confirmation_source=expiry`, hitungan mundur di halaman sukses & portal, 23 test (`tests/Feature/PaymentExpiryTest.php`) |
| 3 | Ulasan terverifikasi | ✅ **selesai** | tabel `reviews` (unik per pembeli+produk), `ReviewController@store`, bagian ulasan di halaman produk + kartu katalog, 24 test (`tests/Feature/ReviewTest.php`) |
| 4 | WhatsApp CS + FAQ per produk | ✅ **selesai** | `config/store.php` + `App\Support\Contact`, kolom JSON `game_titles.faqs`, FAQ akordeon per game di halaman top up, tombol WA di header/FAQ/footer, 7 test (`tests/Feature/StoreHelpTest.php`) |

Catatan implementasi #4 (dengan alasan):
- **Nomor WA dibaca dari `config/store.php`** (`STORE_WHATSAPP` di `.env`), bukan hardcode di view.
  `Contact::whatsappUrl()` mengembalikan `null` bila nomor kosong — tautan rusak tidak pernah
  ditampilkan (tombol hilang otomatis, diuji tes).
- **FAQ per game disimpan sebagai JSON terstruktur** (`question`/`answer`) di kolom
  `game_titles.faqs`, bukan teks HTML mentah. `GameTitle::faqItems()` membuang entri korup
  (bentuk salah, kosong, tag HTML) sebelum dirender; output tetap aman lewat escape Blade `{{ }}`.
- **Akordeon memakai `<details>` native + kelas `.faq-item` yang sudah ada** — tanpa JavaScript,
  tanpa CSS baru.
- **FAQ hanya di halaman top up per game** (bukan per produk marketplace): pertanyaan pembeli
  top up bersifat per-game (region, User ID, durasi), sementara halaman produk marketplace tidak
  punya padanan FAQ-nya — batas scope yang disengaja, bukan kelalaian.

Catatan implementasi #2 (menyimpang dari rencana awal, dengan alasan):
- Pembatalan otomatis ditandai lewat **`confirmation_source = expiry`** (konstanta baru
  `OrderStatus::SOURCE_EXPIRY`) plus `failure_reason`, bukan kolom `expired_at` terpisah. Satu kolom
  yang sudah ada sudah cukup untuk membedakan "dibatalkan pembeli" (kolom kosong) dari "dibatalkan
  sistem" (kolom terisi) — tanpa menambah kolom yang hanya dipakai sekali baca.
- Pesanan yang pembelinya **sudah menandai "sudah bayar" dikecualikan** dari auto-batal. Rencana
  awal menyebut semua `pending`; mengetatkan ini lebih jujur karena uang mungkin sudah berpindah —
  keputusannya milik admin, bukan jam.
- **Buka ulang oleh admin memulai jendela bayar baru.** Tanpa ini, pesanan yang dibuka kembali akan
  langsung kedaluwarsa lagi pada halaman berikutnya.
- Pembatalan juga dijalankan pada **daftar admin & riwayat pembeli**, supaya antrean verifikasi dan
  riwayat tidak menampilkan pesanan yang sebenarnya sudah mati.

Catatan implementasi #3 (dengan alasan):
- **Skema: ulasan hanya untuk produk marketplace**, tanpa tabel polimorfik. Alasannya jujur: halaman
  `topup/show` adalah halaman per-**Game** (grid radio nominal), jadi `Product` (nominal top up) tidak
  punya halaman detail sama sekali — ulasan top up tidak punya tempat tampil. Ini **batas scope yang
  disengaja**, bukan kelalaian: kalau suatu saat tiap nominal punya halaman sendiri, migrasinya lewat
  kolom `reviewable_type`/`reviewable_id` + memindahkan URL detail.
- **Kunci unik `(user_id, marketplace_product_id)`**, lebih ketat dari rencana awal "1 ulasan per
  pesanan". Konsekuensinya: pembelian berulang tidak menaikkan rata-rata rating lewat suara ganda —
  rata-rata selalu dihitung dari jumlah pembeli yang berbeda, bukan jumlah pesanan. Kiriman ulang
  berfungsi sebagai **perbarui** (`updateOrCreate`).
- **Bukti pembelian tersimpan di kolom `marketplace_order_id`**, bukan cuma disimpulkan dari status.
  Karena itu saat admin **membatalkan konfirmasi** (`success → failed`), `MarketplaceOrderService`
  menghapus ulasan `WHERE marketplace_order_id = order.id` saja — dasar klaim "Pembelian terverifikasi"
  hilang bersama pesanannya, sementara ulasan dari pesanan lain milik pembeli yang sama tetap utuh.
- **Tidak ada badge rating di kartu katalog sebelum ada ulasan** (dan tidak pernah ada angka palsu).
  Semua angka datang dari `withCount('reviews')->withAvg('reviews','rating')`; tanpa baris di tabel
  hasilnya `null`, lalu halaman menampilkan **"Belum ada ulasan"**, bukan "0.0".
- **Nama penulis disamarkan** ("Budi Santoso" → "Budi S.") karena halaman produk bisa dibuka siapa
  saja tanpa login. Formulir ulasan hanya muncul di halaman produk ketika pengunjung benar-benar punya
  pesanan **Selesai** atas produk itu (`$myOrder` di `MarketplaceController::show`) — jadi tidak ada
  tombol yang menyesatkan.
- **Batas scope yang ditunda ke P1:** moderasi/admin approve, notifikasi ke penjual, dan ulasan untuk
  produk top up. Ketiganya sengaja tidak dikerjakan sekarang, bukan terlewat.

### P1 — nilai jelas, biaya sedang

5. Perluas kanal bayar di portal: **Alfamart/Indomaret** + tambahan VA bank (tetap simulasi, dilabeli jujur).
6. Feed **"aktivitas terbaru"** publik dengan penyamaran identitas.
7. **Kalkulator** gratis (winrate MLBB) sebagai mesin trafik organik.

### P2 — ekspansi

8. Sistem **saldo** (mutasi + isi ulang) dan **program afiliasi**.
9. **Leaderboard** bulanan.
10. Kategori **jasa** (joki/sewa) sebagai jenis produk baru.

---

## 12. Catatan Metodologi & Keterbatasan

- Situs live **memblokir akses otomatis** (Cloudflare challenge) — bahkan via browser
  sungguhan headless. Analisis memakai snapshot Wayback Machine (2023, 2025, 2026).
- Karena snapshot bersifat titik-waktu, **fitur bisa sudah berubah** di situs live.
  Temuan yang rapuh (mis. ulasan kosong) sebaiknya diverifikasi manual sebelum dijadikan dasar keputusan.
- Inventory URL dibatasi `limit` pada API CDX, jadi kemungkinan ada rute yang tidak terpetakan.
- Bagian **opini/penilaian** sudah ditandai eksplisit; sisanya berbasis bukti yang bisa dicek ulang.

---

*Laporan ini disimpan sebagai `docs/review-takapedia.md`. Lihat juga `README.md` untuk
dokumentasi fitur GantinoID Store.*
