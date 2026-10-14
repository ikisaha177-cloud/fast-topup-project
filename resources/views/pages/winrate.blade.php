@extends('layouts.app')

@section('title', 'Kalkulator Winrate MLBB Gratis - FastTopup Store')
@section('canonical', route('page.winrate'))
@section('og_title', 'Kalkulator Winrate MLBB Gratis')
@section('og_description', 'Hitung winrate Mobile Legends kamu secara instan: masukkan jumlah menang dan kalah untuk tahu persentase winrate beserta ringkasan total pertandingan. Gratis, tanpa login.')

@section('content')
<section class="page-banner">
    @include('partials.starfield', ['compact' => true])

    <div class="container">
        <span class="eyebrow">Tools Gratis</span>
        <h1>Kalkulator Winrate MLBB</h1>
        <p>Masukkan jumlah menang dan kalah, langsung tahu winrate kamu. Gratis, tanpa login, dan hasilnya bisa dibagikan lewat tautan halaman ini.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="detail-grid">
            <div class="card legal-body">
                <h2>Hitung Winrate</h2>
                <p>Isi jumlah pertandingan yang kamu menangkan dan yang kalahkan di Mobile Legends, lalu tekan tombolnya.</p>

                <form method="GET" action="{{ route('page.winrate') }}" class="winrate-form">
                    <div class="form-group">
                        <label class="form-label" for="menang">Jumlah Menang</label>
                        <input class="form-control" type="number" inputmode="numeric" id="menang" name="menang"
                               min="0" max="1000000" required
                               value="{{ old('menang', request('menang')) }}" placeholder="Contoh: 120">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="kalah">Jumlah Kalah</label>
                        <input class="form-control" type="number" inputmode="numeric" id="kalah" name="kalah"
                               min="0" max="1000000" required
                               value="{{ old('kalah', request('kalah')) }}" placeholder="Contoh: 80">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Hitung Winrate</button>
                </form>

                @if($error)
                    <div class="alert alert-error" style="margin-top:18px;">{{ $error }}</div>
                @elseif($result)
                    <div class="winrate-result" aria-live="polite">
                        <span class="eyebrow">Winrate Kamu</span>
                        <strong class="winrate-value">{{ $result['winrate'] }}%</strong>
                        <p class="winrate-summary">
                            {{ $result['menang'] }} menang &bull; {{ $result['kalah'] }} kalah &bull;
                            total {{ $result['total'] }} pertandingan
                        </p>
                    </div>
                @else
                    <div class="winrate-hint">
                        Hasil hitungan akan tampil di sini. Contoh: menang 120, kalah 80 â†’ winrate 60%.
                    </div>
                @endif
            </div>

            <div class="card legal-body">
                <h2>Cara Menghitung Winrate</h2>
                <p>Winrate adalah perbandingan kemenangan terhadap seluruh pertandingan yang sudah dimainkan:</p>
                <p class="winrate-formula">Winrate = (Menang &divide; (Menang + Kalah)) &times; 100%</p>
                <p>Contohnya bila kamu menang 120 kali dan kalah 80 kali: 120 &divide; 200 &times; 100% = <strong>60%</strong>. Angka yang sama persis bisa kamu dapatkan dari kalkulator di samping tanpa menghitung sendiri.</p>

                <h2>Kenapa Winrate Penting?</h2>
                <p>Winrate yang sehat menunjukkan konsistensi permainan, bukan cuma jumlah pertandingan. Umumnya winrate di atas 50% berarti kamu lebih sering menang daripada kalah, sedangkan 60% ke atas tergolong kuat untuk satu musim. Winrate bukan satu-satunya penentu rank â€” hero power dan poin bintang ikut menghitung â€” tapi angka ini gambaran paling cepat dari performamu.</p>

                <h2>Tips Menaikkan Winrate</h2>
                <ul class="legal-steps">
                    <li>Mainkan role dan hero yang benar-benar kamu kuasai, bukan ikut-ikutan meta.</li>
                    <li>Push rank di jam bermain yang tenang dan hindari rentetan kekalahan beruntun.</li>
                    <li>Perhatikan draft â€” komposisi tim yang seimbang menang lebih sering di early game.</li>
                    <li>Evaluasimu setelah tiap pertandingan kalah: objektif apa yang hilang, bukan cuma KDA.</li>
                </ul>

                <div class="review-cta" style="margin-top:22px;">
                    <a href="{{ route('topup.show', 'mobile-legends') }}" class="btn btn-primary">
                        Top Up Mobile Legends Sekarang
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

