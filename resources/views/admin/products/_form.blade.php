{{-- Form dipakai untuk create dan edit produk top up --}}
<div class="form-group">
    <label class="form-label" for="game_title_id">Game</label>
    <select id="game_title_id" name="game_title_id" class="form-control" required>
        <option value="">-- Pilih Game --</option>
        @foreach($games as $game)
            <option value="{{ $game->id }}"
                {{ (string) old('game_title_id', $product->game_title_id ?? '') === (string) $game->id ? 'selected' : '' }}>
                {{ $game->name }}
            </option>
        @endforeach
    </select>
    @error('game_title_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="form-group">
    <label class="form-label" for="name">Nama Produk</label>
    <input type="text" id="name" name="name" class="form-control"
           value="{{ old('name', $product->name ?? '') }}" placeholder="Contoh: 86 Diamonds" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="form-group">
    <label class="form-label" for="description">Deskripsi (opsional)</label>
    <textarea id="description" name="description" class="form-control" rows="3">{{ old('description', $product->description ?? '') }}</textarea>
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="grid grid-2">
    <div class="form-group">
        <label class="form-label" for="price">Harga (Rupiah)</label>
        <input type="number" id="price" name="price" class="form-control"
               value="{{ old('price', $product->price ?? '') }}" min="0" required>
        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="stock">Stok</label>
        <input type="number" id="stock" name="stock" class="form-control"
               value="{{ old('stock', $product->stock ?? 100) }}" min="0" required>
        @error('stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="form-group checkbox">
    <input type="checkbox" id="is_active" name="is_active" value="1"
        {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}>
    <label for="is_active" style="cursor:pointer;">Aktif</label>
</div>
