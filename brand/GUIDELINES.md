# FastTop-up Store â€” Logo Guidelines (compact)

> Untuk non-desainer. Lebih banyak "do" daripada "don't".

## 1. The logo

- **Idea**: tiga batang naik yang puncaknya melebar jadi panah 60Â° â€” progres, kredit, dan top-up dalam satu bentuk. Simbol berdiri sendiri tanpa huruf, jadi app icon, avatar, dan favicon tidak pernah kehilangan makna.
- **Versions**: primary (horizontal) Â· stacked Â· symbol-only Â· wordmark-only
- **Files**: `kit/*.svg` (master vektor) Â· `export/*` (varian one-colour, square, favicon, app icon, PNG) Â· `renders/kit/*.png` (pratinjau) Â· `slides/` (papan presentasi)

| File | Untuk apa |
|---|---|
| `kit/fasttopup-horizontal.svg` | Logo utama: header web, kop, kartu nama |
| `kit/fasttopup-stacked.svg` | Format persegi: signage, profil sosial |
| `kit/fasttopup-symbol.svg` | Simbol saja: avatar, watermark, ikon aplikasi |
| `kit/fasttopup-symbol-small.svg` | Simbol 2 batang: dipakai di bawah 32 px |
| `kit/fasttopup-symbol-two-tone.svg` | Simbol 2 warna untuk latar gelap |
| `kit/fasttopup-horizontal-dark.svg` | Lockup kuning untuk latar gelap |
| `kit/fasttopup-wordmark.svg` | Wordmark saja (tanpa simbol) |
| `export/fasttopup-symbol-favicon.svg` + `favicon.ico` | Tab browser / favicon |
| `export/fasttopup-symbol-app-icon.svg` | Ikon aplikasi di tile gelap |
| `export/*-black.svg`, `*-white.svg`, `*-mono-ffd60a.svg` | Versi satu warna |

## 2. Clear space

Sisakan ruang kosong minimal **1 Ã— lebar satu batang simbol** di keempat sisi logo. Ruang ini ikut mengecil bersama logo â€” jangan pernah pakai jarak tetap dalam piksel.

## 3. Minimum size

| Version | Screen | Print |
|---|---|---|
| Horizontal | 140 px lebar | 35 mm |
| Stacked | 110 px lebar | 28 mm |
| Symbol | 16 px (pakai file `-small` / `favicon`) | 6 mm |
| Wordmark | 100 px lebar | 25 mm |

Di bawah 32 px selalu pakai `fasttopup-symbol-small` (2 batang) â€” counter huruf `O` pada subline "STORE" menutup di bawah itu.

## 4. Colour

| Name | HEX | RGB | CMYK | Pantone |
|---|---|---|---|---|
| Brand Yellow | `#FFD60A` | 255, 214, 10 | 0 / 16 / 96 / 0 | â‰ˆ 109 C (cek dengan swatch fisik) |
| Ink | `#0B0A10` | 11, 10, 16 | 31 / 37 / 0 / 94 | â‰ˆ Black 6 C |
| Paper | `#FFFFFF` | 255, 255, 255 | 0 / 0 / 0 / 0 | â€” |

**Rasio kontras (WCAG):**

- Brand Yellow di atas Ink â†’ **14,0 : 1** (AAA)
- Ink di atas Brand Yellow â†’ **14,0 : 1** (AAA)
- Ink di atas Paper â†’ **19,7 : 1** (AAA)
- Brand Yellow di atas Paper â†’ **1,4 : 1** â€” **jangan dipakai.** Kuning neon tidak terbaca di putih.

**Approved logo/background pairs**: full colour (Ink) di atas putih Â· Brand Yellow di atas Ink atau hitam Â· putih di atas Ink Â· hitam di atas putih.
Di atas foto: pakai versi putih atau hitam pada area yang tenang, atau taruh di dalam kontainer berwarna solid.

## 5. Typography

- **Wordmark**: Plus Jakarta Sans **ExtraBold**, tracking 4,9% tinggi huruf (subline "STORE" 11,8%), di-outline-kan jadi vektor â€” bukan `<text>`, jadi tidak bergantung pada font terpasang.
- **Headline web**: Sora Â· **Body web**: Plus Jakarta Sans (sudah dipakai di produk `topup-market`).
- **Fallback web**: `system-ui, -apple-system, "Segoe UI", sans-serif`.
- **Lisensi**: Plus Jakarta Sans dan Sora keduanya **SIL Open Font License 1.1**. OFL mengizinkan penggunaan bentuk huruf untuk logo. Jika folder `fonts/` ikut dibagikan, sertakan teks lisensi OFL bersamanya.

## 6. Don'ts

Jangan diregangkan atau dipipihkan Â· jangan mengganti warna di luar palet Â· jangan diputar Â· jangan ditambah bayangan, outline, gradien, atau efek Â· jangan mengubah jarak atau ukuran bagian lockup Â· jangan ditaruh di atas latar ramai tanpa kontainer Â· jangan mengetik ulang wordmark memakai font â€” selalu pakai file SVG-nya.

## 7. Contact / master

Master file: `topup-market\brand\` (`kit/`, `export/`).
Pertanyaan penggunaan: pemilik brand.

---

# Handover notes

## Hasil audit (`svg_audit.py`)

| File | Skor | Sisa temuan |
|---|---|---|
| `fasttopup-symbol.svg` | 99/100 | INFO: simbol optik sedikit di atas pusat (sengaja) |
| `fasttopup-symbol-small.svg` | 100/100 | bersih |
| `fasttopup-symbol-two-tone.svg` | 99/100 | INFO: sama seperti di atas |
| `fasttopup-horizontal.svg` | 99/100 | INFO: 312 anchor (tipikal outline font) |
| `fasttopup-horizontal-dark.svg` | 99/100 | idem |
| `fasttopup-stacked.svg` | 83/100 | WARN: sudut 72,1Â° pada diagonal `A` Â· WARN: counter `O` subline |
| `fasttopup-wordmark.svg` | 90/100 | WARN: sudut 72,1Â° pada diagonal `A` |

Kedua WARN sisa bukan cacat konstruksi:

- **72,1Â°** itu diagonal huruf `A` bawaan Plus Jakarta Sans â€” geometri typeface, bukan garis yang kita gambar. Menyetelnya ke 75Â° akan mendistorsi huruf.
- **Counter `O` di "STORE"** hanya muncul di lockup bertingkat karena kanvasnya lebih tinggi (ambang `1/32 tinggi`). Ukuran minimum di Â§3 sudah menanganinya; di bawah 32 px gunakan varian `-small`.

## Hasil pengujian

- **Uji 16/24/32/64 px** â€” simbol `-small` tetap terbaca; batang tidak menyatu.
- **Uji satu warna** â€” `export/*-black`, `*-white`, `*-mono-ffd60a` semuanya utuh (tidak ada "fake knockout" yang jadi blob, karena semua lubang kontur asli pakai `fill-rule="nonzero"`).
- **Uji latar gelap** â€” `fasttopup-horizontal-dark` dan `fasttopup-symbol-two-tone` kontras 14:1 terhadap Ink.
- **Letter test** â€” `FASTTOP-UP` terbaca benar termasuk silang `TT` (tracking 4,9% cap) dan tanda hubung.

## Catatan teknis

- Outline glyph diekstrak dari TTF lewat WPF `GlyphTypeface` (`extract_glyphs.ps1`) â†’ `glyphs/*.json` â†’ disusun `build_wordmark.py`.
- `fill-rule="nonzero"` wajib: kontur TrueType sengaja saling tumpang-tindih. `evenodd` membatalkan tumpang-tindih itu jadi garis putih tipis di tengah batang huruf â€” bug ini sudah ditemukan dan diperbaiki.
- Tidak ada `<text>`, gradien, filter, atau raster di file mana pun.

## Open items

- Nilai Pantone masih perkiraan dari HEX â€” cocokkan dengan swatch fisik sebelum cetak.
- Belum ada master PDF/AI/EPS (butuh tool tambahan); SVG sudah cukup untuk web dan sebagian besar printer.
- Versi putih belum di-thin. Bentuk simbol adalah blok solid, jadi menyusutkannya butuh offset poligon sungguhan (bukan stroke) supaya tidak jadi fake knockout. Untuk sementara: di latar gelap utamakan `fasttopup-horizontal-dark` (kuning) atau `symbol-two-tone`; putih dipakai hanya untuk cetak satu warna, di mana tebal tambahannya tidak terasa.
- `presentation.html` + `slides/*.png` untuk presentasi klien; `presentation-spec.json` bisa diedit lalu dijalankan ulang.
