@extends('layouts.app')

@section('title', 'Profile - GantinoID Store')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div>
                <h1 class="section-title">Profil &amp; Pengaturan</h1>
                <p class="section-subtitle mb-0">Kelola data akun dan keamanan kamu.</p>
            </div>
        </div>

        @include('partials.account-tabs')

        <div class="card" style="display:flex; align-items:center; gap:18px; flex-wrap:wrap; margin-bottom:20px;">
            <span class="nav-avatar" style="width:56px; height:56px; font-size:24px;">
                {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
            </span>
            <div style="flex:1 1 200px;">
                <h2 class="mb-0" style="font-size:20px;">{{ $user->name }}</h2>
                <span class="muted">{{ $user->email }}</span>
            </div>
            @if(auth()->user()->isAdmin())
                <span class="role-badge">Admin</span>
            @else
                <span class="badge badge-info">Member</span>
            @endif
        </div>

        <div class="kpi-row">
            <div class="card stat-card">
                <span class="stat-number">{{ $stats['transactions'] }}</span>
                <span class="stat-label">Transaksi Top Up</span>
            </div>
            <div class="card stat-card">
                <span class="stat-number">{{ $stats['orders'] }}</span>
                <span class="stat-label">Pesanan Marketplace</span>
            </div>
            <div class="card stat-card is-accent">
                <span class="stat-number">Rp {{ number_format($stats['total_spent'], 0, ',', '.') }}</span>
                <span class="stat-label">Total Belanja</span>
            </div>
            <div class="card stat-card">
                <span class="stat-number">{{ $user->created_at->format('d-m-Y') }}</span>
                <span class="stat-label">Terdaftar</span>
            </div>
        </div>

        <div class="grid grid-2">
            <div class="card">
                <h3>Info Akun</h3>
                <div class="summary-row"><span>Nama</span><span>{{ $user->name }}</span></div>
                <div class="summary-row"><span>Email</span><span>{{ $user->email }}</span></div>
                <div class="summary-row"><span>Peran</span><span>{{ $user->role === 'admin' ? 'Administrator' : 'Pembeli' }}</span></div>
                <div class="summary-row"><span>Belanja Top Up</span><span>Rp {{ number_format($stats['topup_spent'], 0, ',', '.') }}</span></div>
                <div class="summary-row"><span>Belanja Marketplace</span><span>Rp {{ number_format($stats['market_spent'], 0, ',', '.') }}</span></div>
                <div class="summary-row"><span>Terdaftar</span><span>{{ $user->created_at->format('d-m-Y') }}</span></div>
                <div class="summary-row"><span>Terakhir Diperbarui</span><span>{{ $user->updated_at->format('d-m-Y') }}</span></div>
            </div>

            <div class="card">
                <h3>Ubah Data</h3>
                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label class="form-label" for="name">Nama</label>
                        <input type="text" id="name" name="name" class="form-control"
                               value="{{ old('name', $user->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="current_password">Password Lama</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                        <div class="form-hint">Wajib untuk menyimpan perubahan.</div>
                        @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="grid grid-2">
                        <div class="form-group">
                            <label class="form-label" for="new_password">Password Baru</label>
                            <input type="password" id="new_password" name="new_password" class="form-control">
                            @error('new_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="new_password_confirmation">Ulangi Password Baru</label>
                            <input type="password" id="new_password_confirmation" name="new_password_confirmation" class="form-control">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Simpan Perubahan</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
