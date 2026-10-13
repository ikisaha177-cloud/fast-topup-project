@php
    /**
     * Hitungan mundur batas waktu pembayaran.
     *
     * Hanya tampil kalau batas waktunya memang masih relevan — supaya tidak ada
     * angka mundur untuk pesanan yang tidak punya batas waktu (baris lama),
     * yang sudah ditandai "sudah bayar", atau yang sudah selesai/batal.
     *
     * Hitungan mundurnya jujur: waktu habis → halaman dimuat ulang → server
     * membatalkan pesanannya lewat lazy expiry, jadi yang tampil berikutnya
     * adalah keadaan sebenarnya, bukan angka 00:00:00 yang menggantung.
     *
     * @param  \App\Models\Transaction|\App\Models\MarketplaceOrder  $order
     */
    $deadline     = $order->expires_at ?? null;
    $showDeadline = $deadline !== null && $order->isPending() && ! $order->isPaymentClaimed();
@endphp

@if($showDeadline)
    <div class="pay-deadline" data-countdown="{{ $deadline->toIso8601String() }}">
        <span class="pay-deadline-icon">@include('partials.icon', ['name' => 'clock', 'size' => 17])</span>
        <span class="pay-deadline-body">
            <span class="pay-deadline-label">Selesaikan pembayaran dalam</span>
            <strong class="pay-deadline-value" data-countdown-value>--:--:--</strong>
        </span>
        <span class="pay-deadline-until">sampai {{ $deadline->format('d-m-Y H:i') }} WIB</span>
    </div>

    <script>
        (function () {
            // Beberapa halaman memuat partial ini sekali; penjaga ini membuat
            // penjadwalannya tetap tunggal walau partial-nya dipanggil berkali-kali.
            if (window.__gantinoDeadlineTimer) {
                return;
            }

            var URGENT_SECONDS = 600; // 10 menit terakhir

            function pad(n) {
                return (n < 10 ? '0' : '') + n;
            }

            function format(totalSeconds) {
                var hours   = Math.floor(totalSeconds / 3600);
                var minutes = Math.floor((totalSeconds % 3600) / 60);
                var seconds = totalSeconds % 60;

                return pad(hours) + ':' + pad(minutes) + ':' + pad(seconds);
            }

            function render() {
                var boxes = document.querySelectorAll('[data-countdown]');
                var expired = false;

                boxes.forEach(function (box) {
                    var target = new Date(box.getAttribute('data-countdown')).getTime();
                    var value  = box.querySelector('[data-countdown-value]');

                    if (isNaN(target)) {
                        return;
                    }

                    var remaining = Math.max(0, Math.floor((target - Date.now()) / 1000));

                    if (value) {
                        value.textContent = format(remaining);
                    }

                    box.classList.toggle('is-urgent', remaining > 0 && remaining <= URGENT_SECONDS);

                    if (remaining === 0) {
                        expired = true;
                    }
                });

                // Waktu habis: minta server memutuskan. Tanpa reload, pembeli
                // akan melihat angka 00:00:00 dan tombol bayar yang menipu.
                if (expired) {
                    window.location.reload();

                    return false;
                }

                return true;
            }

            if (render()) {
                window.__gantinoDeadlineTimer = setInterval(render, 1000);
            }
        })();
    </script>
@endif
