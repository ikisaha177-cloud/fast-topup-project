{{-- Form dipakai untuk create dan edit game --}}
<div class="form-group">
    <label class="form-label" for="name">Nama Game</label>
    <input type="text" id="name" name="name" class="form-control"
           value="{{ old('name', $game->name ?? '') }}" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="form-group">
    <label class="form-label" for="category">Kategori</label>
    <input type="text" id="category" name="category" class="form-control"
           value="{{ old('category', $game->category ?? '') }}" placeholder="Contoh: MOBA, FPS, Battle Royale">
    @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="form-group">
    <label class="form-label" for="icon">Icon Game</label>
    @if(! empty($game->icon))
        @include('partials.game-icon', ['game' => $game, 'size' => 'sm'])
    @endif
    <input type="file" id="icon" name="icon" class="form-control mt-8" accept="image/*">
    <div class="form-hint">
        Format jpg, png, webp, atau svg. Maksimal 2 MB. Kosongkan bila tidak ingin mengubah icon.
    </div>
    @error('icon') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="form-group checkbox">
    <input type="checkbox" id="is_active" name="is_active" value="1"
        {{ old('is_active', $game->is_active ?? true) ? 'checked' : '' }}>
    <label for="is_active" style="cursor:pointer;">Aktif</label>
</div>
