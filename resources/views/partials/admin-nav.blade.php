{{-- Menu navigasi khusus admin --}}
<div class="admin-nav">
    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" {!! aria_current(request()->routeIs('admin.dashboard')) !!}>Dashboard</a>
    <a href="{{ route('admin.games.index') }}" class="{{ request()->routeIs('admin.games.*') ? 'active' : '' }}" {!! aria_current(request()->routeIs('admin.games.*')) !!}>Game</a>
    <a href="{{ route('admin.products.index') }}" class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}" {!! aria_current(request()->routeIs('admin.products.*')) !!}>Produk Top Up</a>
    <a href="{{ route('admin.marketplace.index') }}" class="{{ request()->routeIs('admin.marketplace.*') ? 'active' : '' }}" {!! aria_current(request()->routeIs('admin.marketplace.*')) !!}>Marketplace</a>
    <a href="{{ route('admin.promos.index') }}" class="{{ request()->routeIs('admin.promos.*') ? 'active' : '' }}" {!! aria_current(request()->routeIs('admin.promos.*')) !!}>Kode Promo</a>
    <a href="{{ route('admin.transactions.index') }}" class="{{ request()->routeIs('admin.transactions.*') ? 'active' : '' }}" {!! aria_current(request()->routeIs('admin.transactions.*')) !!}>Transaksi</a>
    <a href="{{ route('admin.orders.index') }}" class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" {!! aria_current(request()->routeIs('admin.orders.*')) !!}>Pesanan</a>
    <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" {!! aria_current(request()->routeIs('admin.reports.*')) !!}>Laporan</a>
    <a href="{{ route('admin.activity.index') }}" class="{{ request()->routeIs('admin.activity.*') ? 'active' : '' }}" {!! aria_current(request()->routeIs('admin.activity.*')) !!}>Log Aktivitas</a>
</div>
