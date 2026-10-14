@extends('layouts.app')

@section('title', 'Syarat & Ketentuan - FastTopup Store')
@section('og_title', 'Syarat & Ketentuan FastTopup Store')
@section('og_description', 'Aturan penggunaan layanan top up game dan marketplace FastTopup Store, termasuk hak dan kewajiban pengguna.')

@section('content')
<section class="page-banner">
    @include('partials.starfield', ['compact' => true])

    <div class="container">
        <span class="eyebrow">Kebijakan</span>
        <h1>Syarat &amp; Ketentuan</h1>
        <p>Dengan menggunakan layanan FastTopup Store, kamu dianggap telah membaca dan menyetujui ketentuan berikut.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        @include('partials.legal-tabs')

        <div class="detail-grid">
            <div class="card legal-body">
                <h2>1. Ketentuan Umum</h2>
                <p>FastTopup Store adalah layanan top up game dan marketplace item game. Layanan ditujukan untuk pengguna yang berusia minimal 13 tahun atau telah memperoleh izin dari orang tua/wali.</p>

                <h2>2. Akun Pengguna</h2>
                <ul>
                    <li>Pengguna bertanggung jawab menjaga kerahasiaan kata sandi akunnya.</li>
                    <li>Seluruh aktivitas yang terjadi melalui akun pengguna menjadi tanggung jawab pemilik akun.</li>
                    <li>Pengguna wajib memberikan data yang benar dan terkini.</li>
                </ul>

                <h2>3. Pesanan &amp; Pembayaran</h2>
                <ul>
                    <li>Pesanan dibuat dengan mengisi data yang diminta (mis. User ID game) dan memilih nominal produk.</li>
                    <li>Pembayaran diproses melalui metode yang tersedia. Pesanan diverifikasi oleh admin sebelum dinyatakan selesai.</li>
                    <li>Pastikan data pesanan sudah benar. Pesanan yang telah selesai diproses bersifat final.</li>
                    <li>Bila pembayaran tidak dapat diverifikasi, pesanan ditandai <strong>Gagal</strong> dan stok dikembalikan.</li>
                </ul>

                <h2>4. Pembatalan &amp; Pengembalian</h2>
                <ul>
                    <li>Pesanan yang masih menunggu konfirmasi dapat dibatalkan oleh pengguna; stok akan dikembalikan.</li>
                    <li>Pengembalian dana untuk pesanan gagal diproses sesuai kebijakan metode pembayaran yang digunakan.</li>
                    <li>Admin dapat memperbaiki kekeliruan verifikasi (mis. salah konfirmasi) dengan mengembalikan status pesanan.</li>
                </ul>

                <h2>5. Larangan</h2>
                <p>Pengguna dilarang:</p>
                <ul>
                    <li>Menggunakan layanan untuk aktivitas yang melanggar hukum.</li>
                    <li>Melakukan penipuan, termasuk memalsukan bukti pembayaran.</li>
                    <li>Mengganggu, menyerang, atau mencoba mengakses sistem tanpa izin.</li>
                </ul>

                <h2>6. Batasan Tanggung Jawab</h2>
                <p>FastTopup Store berupaya menjaga layanan tetap tersedia dan akurat, namun tidak bertanggung jawab atas kerugian tidak langsung yang timbul dari gangguan layanan di luar kendali kami. Item game yang diperjualbelikan di marketplace disediakan oleh penjual pihak ketiga.</p>

                <h2>7. Perubahan Ketentuan</h2>
                <p>Ketentuan ini dapat diperbarui sewaktu-waktu. Versi terbaru akan selalu ditampilkan pada halaman ini.</p>

                <h2>8. Kontak</h2>
                <p>Pertanyaan mengenai ketentuan ini dapat dikirim ke <a href="mailto:support@FastTopup.test">support@FastTopup.test</a>.</p>
            </div>

            <div class="card summary-card">
                <h3>Ringkasan</h3>
                <div class="summary-row"><span>Berlaku sejak</span><span>{{ \Carbon\Carbon::parse($updatedAt)->translatedFormat('d F Y') }}</span></div>
                <div class="summary-row"><span>Berlaku untuk</span><span>Semua pengguna</span></div>
                <p class="muted mt-16 mb-0" style="font-size:12.5px;">Dokumen ini bersifat informatif dan tidak menggantikan nasihat hukum.</p>
            </div>
        </div>
    </div>
</section>
@endsection

