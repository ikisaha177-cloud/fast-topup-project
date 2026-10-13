{{-- Baris potongan promo untuk detail transaksi/pesanan.
     Hanya tampil bila ada potongan, jadi pesanan tanpa promo tidak berubah.

     Variabel:
       $promoCode : kode promo yang dipakai.
       $discount  : potongan rupiah. --}}
@if(($discount ?? 0) > 0)
    <div class="summary-row">
        <span>Kode Promo</span>
        <span class="mono">{{ $promoCode }}</span>
    </div>
    <div class="summary-row">
        <span>Potongan Promo</span>
        <span class="discount-amount">− Rp {{ number_format($discount, 0, ',', '.') }}</span>
    </div>
@endif
