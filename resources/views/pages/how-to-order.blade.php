@extends('layouts.app')

@section('title', 'Cara Order - GantinoID Store')
@section('og_title', 'Cara Order di GantinoID Store')
@section('og_description', 'Panduan langkah demi langkah top up game dan belanja marketplace: pilih produk, bayar, unggah bukti, lalu tunggu verifikasi admin.')

@section('content')
<section class="page-banner">
    <div class="container">
        <span class="eyebrow">Bantuan</span>
        <h1>Cara Order</h1>
        <p>Panduan singkat dari memilih produk sampai pesanan selesai — untuk top up game maupun marketplace.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        @include('partials.legal-tabs')

        <div class="detail-grid">
            <div class="card legal-body">
                <h2>Top Up Game</h2>
                <ol class="legal-steps">
                    <li>
                        <strong>Pilih game.</strong> Buka <a href="{{ route('topup.index') }}">Top Up Game</a>,
                        lalu pilih judul game yang ingin diisi.
                    </li>
                    <li>
                        <strong>Isi data & pilih nominal.</strong> Masukkan User ID (dan Server ID bila diminta),
                        lalu pilih nominal produk. Pastikan data sudah benar — pesanan yang sudah diproses
                        tidak dapat dibatalkan.
                    </li>
                    <li>
                        <strong>Pilih metode pembayaran.</strong> Tersedia QRIS, e-wallet (DANA, GoPay, OVO),
                        dan transfer bank. Ikuti instruksi pembayaran yang tampil.
                    </li>
                    <li>
                        <strong>Tandai & unggah bukti.</strong> Setelah membayar, tekan <em>Saya Sudah Bayar</em>
                        dan (opsional) unggah screenshot/foto bukti transfer. Bukti membantu admin memverifikasi
                        lebih cepat.
                    </li>
                    <li>
                        <strong>Tunggu verifikasi admin.</strong> Admin memeriksa pembayaran lalu menandai
                        pesanan <strong>Selesai</strong> atau <strong>Gagal</strong> (disertai alasan). Kamu
                        mendapat notifikasi email untuk setiap perubahan status.
                    </li>
                </ol>

                <h2>Marketplace</h2>
                <ol class="legal-steps">
                    <li>
                        <strong>Pilih produk.</strong> Buka <a href="{{ route('marketplace.index') }}">Marketplace</a>,
                        telusuri voucher, skin, akun, atau koin. Setiap produk berasal dari penjual yang terdaftar.
                    </li>
                    <li>
                        <strong>Tambahkan ke keranjang.</strong> Atur jumlah, lalu lanjut ke checkout.
                    </li>
                    <li>
                        <strong>Bayar & tandai.</strong> Selesaikan pembayaran, tekan <em>Saya Sudah Bayar</em>,
                        dan unggah bukti bila ada.
                    </li>
                    <li>
                        <strong>Tunggu konfirmasi admin.</strong> Setelah dikonfirmasi, item dikirim ke akun
                        atau kode voucher kamu. Bila gagal, stok otomatis dikembalikan ke penjual.
                    </li>
                </ol>

                <h2>Pertanyaan yang sering muncul</h2>
                <dl class="legal-faq">
                    <dt>Berapa lama verifikasi?</dt>
                    <dd>Biasanya beberapa menit setelah bukti pembayaran diterima. Waktu dapat lebih lama pada jam sibuk.</dd>

                    <dt>Apakah wajib mengunggah bukti?</dt>
                    <dd>Tidak. Unggahan bukti bersifat opsional, tetapi sangat membantu proses verifikasi.</dd>

                    <dt>Bagaimana kalau salah memasukkan User ID?</dt>
                    <dd>Segera hubungi admin sebelum pesanan diverifikasi. Pesanan yang sudah selesai bersifat final.</dd>

                    <dt>Bagaimana kalau salah konfirmasi?</dt>
                    <dd>Admin dapat membatalkan konfirmasi yang keliru sehingga status kembali menjadi Gagal dan stok dikembalikan.</dd>
                </dl>
            </div>

            <div class="card summary-card">
                <h3>Butuh bantuan?</h3>
                <p class="muted">Tim kami siap membantu pada hari kerja.</p>
                <a href="mailto:support@gantinoid.test" class="btn btn-outline btn-block">Email Support</a>
                <a href="{{ route('home') }}#faq" class="btn btn-ghost btn-block mt-8">Pertanyaan Umum</a>
                <p class="muted mt-16 mb-0" style="font-size:12.5px;">Terakhir diperbarui: {{ \Carbon\Carbon::parse($updatedAt)->translatedFormat('d F Y') }}</p>
            </div>
        </div>
    </div>
</section>
@endsection
