{{-- Kotak kode promo, dipakai halaman checkout top up & marketplace.

     Variabel:
       $applyRoute : route POST untuk memasang / melepas kode promo.
       $code       : kode yang sedang aktif (null bila belum ada).
       $discount   : potongan rupiah yang sedang berlaku. --}}
<div class="promo-box">
    <form action="{{ $applyRoute }}" method="POST">
        @csrf
        <label class="form-label" for="promo_code">Kode Promo</label>
        <div class="promo-input">
            <input type="text" id="promo_code" name="code" class="form-control"
                   value="{{ old('promo_code', $code ?? '') }}"
                   placeholder="Contoh: GANTINO10"
                   maxlength="40" autocomplete="off" spellcheck="false"
                   {{ $code ? 'readonly' : '' }}>
            @if($code)
                <button type="submit" name="remove" value="1" class="btn btn-outline">Hapus</button>
            @else
                <button type="submit" class="btn btn-outline">Gunakan</button>
            @endif
        </div>
        <div class="form-hint">
            @if($code)
                Kode <strong class="mono">{{ $code }}</strong> sedang dipakai. Tekan Hapus untuk membatalkan.
            @else
                Punya kode promo? Masukkan lalu tekan Gunakan.
            @endif
        </div>
        @error('promo_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </form>

    @if(($discount ?? 0) > 0)
        <div class="summary-row promo-discount">
            <span>Diskon <span class="mono">{{ $code }}</span></span>
            <span class="discount-amount">− Rp {{ number_format($discount, 0, ',', '.') }}</span>
        </div>
    @endif
</div>
