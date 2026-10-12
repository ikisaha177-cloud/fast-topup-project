{{-- Sub-navigasi area akun: menyatukan Riwayat Transaksi, Pesanan, dan Profil
     dalam satu section "Akun Saya" supaya penempatan fiturnya konsisten. --}}
<div class="account-tabs">
    <a href="{{ route('transactions.index') }}" class="{{ request()->routeIs('transactions.*') ? 'active' : '' }}">
        @include('partials.icon', ['name' => 'receipt', 'size' => 17]) Riwayat Top Up
    </a>
    <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') ? 'active' : '' }}">
        @include('partials.icon', ['name' => 'cart', 'size' => 17]) Pesanan Saya
    </a>
    <a href="{{ route('profile') }}" class="{{ request()->routeIs('profile') ? 'active' : '' }}">
        @include('partials.icon', ['name' => 'user', 'size' => 17]) Profil &amp; Pengaturan
    </a>
</div>
