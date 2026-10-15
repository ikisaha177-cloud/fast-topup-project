{{-- Form dipakai untuk create dan edit kode promo --}}
@php
    $startsAt = old('starts_at', isset($promo) && $promo->starts_at ? $promo->starts_at->format('Y-m-d\TH:i') : '');
    $endsAt   = old('ends_at', isset($promo) && $promo->ends_at ? $promo->ends_at->format('Y-m-d\TH:i') : '');
@endphp

<div class="form-group">
    <label class="form-label" for="code">Kode Promo</label>
    <input type="text" id="code" name="code" class="form-control" style="text-transform:uppercase;"
           value="{{ old('code', $promo->code ?? '') }}" placeholder="Contoh: FASTTOPUP10"
           minlength="3" maxlength="40" required autocomplete="off" spellcheck="false">
    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-hint">Huruf, angka, tanda minus, atau garis bawah. 3–40 karakter.</div>
</div>

<div class="form-group">
    <label class="form-label" for="description">Deskripsi Internal</label>
    <input type="text" id="description" name="description" class="form-control"
           value="{{ old('description', $promo->description ?? '') }}" placeholder="Contoh: Promo akhir pekan">
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-hint">Hanya terlihat oleh admin. Kosongkan bila tidak perlu.</div>
</div>

<div class="grid grid-2" style="gap:16px;">
    <div class="form-group">
        <label class="form-label" for="type">Tipe Potongan</label>
        <select id="type" name="type" class="form-control" required>
            <option value="percent" {{ old('type', $promo->type ?? 'percent') === 'percent' ? 'selected' : '' }}>Persen (%)</option>
            <option value="fixed" {{ old('type', $promo->type ?? '') === 'fixed' ? 'selected' : '' }}>Nominal tetap (Rp)</option>
        </select>
        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="value">Nilai Potongan</label>
        <input type="number" id="value" name="value" class="form-control" min="1" max="100000000"
               value="{{ old('value', $promo->value ?? '') }}" placeholder="Contoh: 10" required>
        @error('value') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-hint">Persen maksimal 100. Untuk nominal, isi dalam rupiah.</div>
    </div>
</div>

<div class="grid grid-2" style="gap:16px;">
    <div class="form-group">
        <label class="form-label" for="min_spend">Minimal Belanja (Rp)</label>
        <input type="number" id="min_spend" name="min_spend" class="form-control" min="0"
               value="{{ old('min_spend', $promo->min_spend ?? 0) }}" placeholder="0">
        @error('min_spend') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-hint">0 = tanpa syarat minimum.</div>
    </div>

    <div class="form-group">
        <label class="form-label" for="max_discount">Maksimal Potongan (Rp)</label>
        <input type="number" id="max_discount" name="max_discount" class="form-control" min="0"
               value="{{ old('max_discount', $promo->max_discount ?? '') }}" placeholder="Tanpa batas">
        @error('max_discount') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-hint">Hanya berlaku untuk tipe persen. Kosongkan bila tanpa batas.</div>
    </div>
</div>

<div class="grid grid-2" style="gap:16px;">
    <div class="form-group">
        <label class="form-label" for="scope">Cakupan</label>
        <select id="scope" name="scope" class="form-control" required>
            <option value="all" {{ old('scope', $promo->scope ?? 'all') === 'all' ? 'selected' : '' }}>Semua layanan</option>
            <option value="topup" {{ old('scope', $promo->scope ?? '') === 'topup' ? 'selected' : '' }}>Top Up Game saja</option>
            <option value="marketplace" {{ old('scope', $promo->scope ?? '') === 'marketplace' ? 'selected' : '' }}>Marketplace saja</option>
        </select>
        @error('scope') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="usage_limit">Batas Pemakaian</label>
        <input type="number" id="usage_limit" name="usage_limit" class="form-control" min="1"
               value="{{ old('usage_limit', $promo->usage_limit ?? '') }}" placeholder="Tanpa batas">
        @error('usage_limit') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-hint">Kosongkan bila mau tanpa batas.</div>
    </div>
</div>

<div class="grid grid-2" style="gap:16px;">
    <div class="form-group">
        <label class="form-label" for="starts_at">Mulai Berlaku</label>
        <input type="datetime-local" id="starts_at" name="starts_at" class="form-control"
               value="{{ $startsAt }}">
        @error('starts_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-hint">Kosongkan = langsung berlaku.</div>
    </div>

    <div class="form-group">
        <label class="form-label" for="ends_at">Berakhir</label>
        <input type="datetime-local" id="ends_at" name="ends_at" class="form-control"
               value="{{ $endsAt }}">
        @error('ends_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-hint">Kosongkan = tidak pernah kedaluwarsa.</div>
    </div>
</div>

<div class="form-group checkbox">
    <input type="checkbox" id="is_active" name="is_active" value="1"
        {{ old('is_active', $promo->is_active ?? true) ? 'checked' : '' }}>
    <label for="is_active" style="cursor:pointer;">Aktif</label>
</div>
