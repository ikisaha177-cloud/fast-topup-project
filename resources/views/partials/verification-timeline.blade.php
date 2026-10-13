@php
    /**
     * Timeline verifikasi vertikal — dipakai bersama oleh detail transaksi
     * top up dan detail pesanan marketplace.
     *
     * Keduanya punya kolom yang sama (status, created_at, payment_claimed_at,
     * payment_proof, confirmed_at, confirmation_source, failure_reason,
     * revoked_at), jadi partial ini menerima model apa pun sebagai $model.
     */
    $status      = $model->status;
    $isPending   = $status === \App\Support\OrderStatus::PENDING;
    $isSuccess   = $status === \App\Support\OrderStatus::SUCCESS;
    $isFailed    = $status === \App\Support\OrderStatus::FAILED;
    $isCancelled = $status === \App\Support\OrderStatus::CANCELLED;

    $claimed   = $model->isPaymentClaimed();
    $confirmed = $model->confirmed_at !== null;

    // State visual tiap langkah: done | current | upcoming | success | failed.
    $steps = [];

    // 1. Pesanan dibuat (selalu selesai).
    $steps[] = [
        'state' => 'done',
        'title' => 'Pesanan Dibuat',
        'time'  => $model->created_at,
        'note'  => 'Pesanan tercatat di sistem.',
    ];

    // 2. Pembayaran / penandaan sudah bayar.
    if ($claimed) {
        $steps[] = [
            'state' => 'done',
            'title' => 'Kamu Menandai Sudah Bayar',
            'time'  => $model->payment_claimed_at,
            'note'  => $isPending
                ? 'Pembayaran menunggu diverifikasi admin.'
                : 'Pembayaran sudah diterima.',
        ];
    } elseif ($isPending) {
        $steps[] = [
            'state' => 'current',
            'title' => 'Menunggu Pembayaran',
            'time'  => null,
            'note'  => 'Selesaikan pembayaran, lalu tandai "Saya Sudah Bayar".',
        ];
    } else {
        $steps[] = [
            'state' => 'upcoming',
            'title' => 'Pembayaran',
            'time'  => null,
            'note'  => 'Tidak ada penandaan pembayaran yang tercatat.',
        ];
    }

    // 3. Bukti pembayaran (opsional — hanya bila diunggah).
    if ($model->hasPaymentProof()) {
        $steps[] = [
            'state' => 'done',
            'title' => 'Bukti Pembayaran Diunggah',
            'time'  => $model->payment_claimed_at,
            'note'  => 'Bukti terlampir membantu admin memverifikasi.',
            'proof' => true,
        ];
    }

    // 4. Verifikasi admin / gateway / otomatis dari bukti.
    if ($confirmed) {
        $byGateway = $model->confirmedByGateway();
        $byAuto    = $model->confirmedByAuto();

        $steps[] = [
            'state' => 'done',
            'title' => $byGateway || $byAuto ? 'Terverifikasi Otomatis' : 'Diverifikasi Admin',
            'time'  => $model->confirmed_at,
            'note'  => $byGateway
                ? 'Dikonfirmasi otomatis oleh gateway pembayaran.'
                : ($byAuto
                    ? 'Dikonfirmasi otomatis oleh sistem dari bukti pembayaran.'
                    : ('Dikonfirmasi oleh ' . ($model->confirmedBy->name ?? 'admin') . '.')),
        ];
    } elseif ($isPending && $claimed) {
        $steps[] = [
            'state' => 'current',
            'title' => 'Menunggu Verifikasi Admin',
            'time'  => null,
            'note'  => 'Admin sedang memeriksa pembayaranmu.',
        ];
    } elseif ($isPending) {
        $steps[] = [
            'state' => 'upcoming',
            'title' => 'Verifikasi Admin',
            'time'  => null,
            'note'  => 'Menunggu pembayaran ditandai terlebih dahulu.',
        ];
    }

    // 4b. Jejak pembatalan konfirmasi (bila admin pernah membatalkan success).
    if ($model->wasRevoked()) {
        $steps[] = [
            'state' => 'failed',
            'title' => 'Konfirmasi Dibatalkan',
            'time'  => $model->revoked_at,
            'note'  => 'Admin membatalkan konfirmasi yang keliru'
                . ($model->revokedBy ? ' (' . $model->revokedBy->name . ')' : '') . '.',
        ];
    }

    // 5. Hasil akhir.
    if ($isSuccess) {
        $steps[] = [
            'state' => 'success',
            'title' => 'Pesanan Selesai',
            'time'  => $model->confirmed_at,
            'note'  => 'Pesanan berhasil diproses.',
        ];
    } elseif ($isFailed) {
        $steps[] = [
            'state' => 'failed',
            'title' => 'Pesanan Gagal',
            'time'  => $model->confirmed_at,
            'note'  => $model->failure_reason
                ? 'Alasan: ' . $model->failure_reason
                : 'Pesanan gagal diproses; stok dikembalikan.',
        ];
    } elseif ($isCancelled) {
        $steps[] = [
            'state' => 'failed',
            'title' => 'Pesanan Dibatalkan',
            'time'  => $model->updated_at,
            'note'  => 'Dibatalkan; stok dikembalikan.',
        ];
    } else {
        $steps[] = [
            'state' => 'upcoming',
            'title' => 'Hasil Akhir',
            'time'  => null,
            'note'  => 'Menunggu keputusan admin.',
        ];
    }
@endphp

<ol class="vtimeline">
    @foreach($steps as $step)
        <li class="vtimeline-item is-{{ $step['state'] }}">
            <div class="vt-head">
                <span class="vt-title">{{ $step['title'] }}</span>
                @if($step['time'])
                    <span class="vt-time">{{ $step['time']->format('d-m-Y H:i') }}</span>
                @endif
            </div>
            <p class="vt-note">{{ $step['note'] }}</p>
            @if(! empty($step['proof']))
                <a class="vt-proof" href="{{ $model->paymentProofUrl() }}" target="_blank" rel="noopener">Lihat bukti pembayaran</a>
            @endif
        </li>
    @endforeach
</ol>
