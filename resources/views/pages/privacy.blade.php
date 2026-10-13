@extends('layouts.app')

@section('title', 'Kebijakan Privasi - TopUpMarket')
@section('og_title', 'Kebijakan Privasi TopUpMarket')
@section('og_description', 'Bagaimana TopUpMarket mengumpulkan, menggunakan, dan melindungi data pribadi pengguna.')

@section('content')
<section class="page-banner">
    <div class="container">
        <span class="eyebrow">Kebijakan</span>
        <h1>Kebijakan Privasi</h1>
        <p>Kami menghargai privasimu. Halaman ini menjelaskan data apa yang kami kumpulkan dan bagaimana kami menjaganya.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        @include('partials.legal-tabs')

        <div class="detail-grid">
            <div class="card legal-body">
                <h2>1. Data yang Kami Kumpulkan</h2>
                <ul>
                    <li><strong>Data akun:</strong> nama, alamat email, dan kata sandi (tersimpan dalam bentuk terenkripsi).</li>
                    <li><strong>Data pesanan:</strong> User ID game, nominal produk, metode pembayaran, dan status pesanan.</li>
                    <li><strong>Bukti pembayaran:</strong> gambar bukti transfer yang kamu unggah secara opsional.</li>
                    <li><strong>Data teknis:</strong> alamat IP dan informasi perangkat untuk keamanan dan pencegahan penyalahgunaan.</li>
                </ul>

                <h2>2. Cara Kami Menggunakan Data</h2>
                <ul>
                    <li>Memproses dan memverifikasi pesananmu.</li>
                    <li>Mengirim notifikasi terkait status pesanan.</li>
                    <li>Menjaga keamanan layanan dan mendeteksi aktivitas mencurigakan.</li>
                    <li>Meningkatkan kualitas layanan.</li>
                </ul>

                <h2>3. Dasar &amp; Penyimpanan</h2>
                <p>Data disimpan hanya selama diperlukan untuk memenuhi tujuan di atas dan kewajiban hukum yang berlaku. Bukti pembayaran disimpan terbatas untuk keperluan verifikasi dan dapat dihapus setelah tidak lagi diperlukan.</p>

                <h2>4. Berbagi Data</h2>
                <p>Kami tidak menjual data pribadimu. Data hanya dibagikan kepada pihak yang diperlukan untuk mengoperasikan layanan (mis. penyedia pembayaran) atau bila diwajibkan oleh hukum.</p>

                <h2>5. Hak Pengguna</h2>
                <ul>
                    <li>Mengakses dan memperbarui data profil melalui halaman <a href="{{ route('profile') }}">Profil</a>.</li>
                    <li>Meminta informasi mengenai data yang kami simpan.</li>
                    <li>Meminta penghapusan data sejauh tidak bertentangan dengan kewajiban hukum.</li>
                </ul>

                <h2>6. Keamanan</h2>
                <p>Kami menerapkan langkah teknis dan organisasi yang wajar untuk melindungi data, termasuk penyimpanan kata sandi terenkripsi dan pembatasan akses. Namun, tidak ada metode transmisi data yang sepenuhnya bebas risiko.</p>

                <h2>7. Kontak</h2>
                <p>Untuk pertanyaan atau permintaan terkait privasi, hubungi <a href="mailto:support@topupmarket.test">support@topupmarket.test</a>.</p>
            </div>

            <div class="card summary-card">
                <h3>Ringkasan</h3>
                <div class="summary-row"><span>Diperbarui</span><span>{{ \Carbon\Carbon::parse($updatedAt)->translatedFormat('d F Y') }}</span></div>
                <div class="summary-row"><span>Kata sandi</span><span>Terenkripsi</span></div>
                <div class="summary-row"><span>Penjualan data</span><span>Tidak pernah</span></div>
                <p class="muted mt-16 mb-0" style="font-size:12.5px;">Dokumen ini bersifat informatif dan tidak menggantikan nasihat hukum.</p>
            </div>
        </div>
    </div>
</section>
@endsection
