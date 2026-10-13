<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="TopUpMarket — top up game dan marketplace voucher, skin, dan akun game dengan pembayaran QRIS, e-wallet, dan transfer bank.">
    <meta name="theme-color" content="#4f46e5">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('favicon.svg') }}">
    <title>@yield('title', 'TopUpMarket — Top Up Game & Marketplace')</title>

    {{-- Open Graph / Twitter Card: tampilan rapi saat dibagikan. --}}
    <meta property="og:site_name" content="TopUpMarket">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('og_title', 'TopUpMarket — Top Up Game & Marketplace')">
    <meta property="og:description" content="@yield('og_description', 'Top up game dan marketplace voucher, skin, dan akun game. Bayar dengan QRIS, e-wallet, atau transfer bank.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', asset('images/placeholder-product.svg'))">
    <meta name="twitter:card" content="summary_large_image">

    @yield('meta')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @stack('styles')
    <script>document.documentElement.classList.add('js');</script>
</head>
<body>
    <a href="#main" class="skip-link">Lewati ke konten utama</a>
    @php
        $cart = session('cart', []);
        $cartCount = 0;
        foreach ($cart as $item) { $cartCount += $item['quantity']; }

        $user = auth()->user();
        $isAdmin = $user && $user->isAdmin();
        $initial = $user ? mb_strtoupper(mb_substr($user->name, 0, 1)) : '?';
    @endphp

    <nav class="navbar">
        <div class="navbar-inner">
            <a href="{{ route('home') }}" class="brand">
                <span class="brand-logo">T</span>
                <span>
                    TopUpMarket
                    <span class="brand-sub">Top Up &amp; Marketplace</span>
                </span>
            </a>

            <button type="button" class="nav-toggle" id="navToggle" aria-label="Buka menu" aria-expanded="false">
                <span class="nav-toggle-icon" data-icon="open">@include('partials.icon', ['name' => 'menu', 'size' => 20])</span>
                <span class="nav-toggle-icon" data-icon="close" hidden>@include('partials.icon', ['name' => 'close', 'size' => 20])</span>
            </button>

            <div class="nav-links" id="navLinks">
                @if($user)
                    @if($isAdmin)
                        <a href="{{ route('home') }}" target="_blank" rel="noopener">Lihat Toko</a>
                        <div class="nav-dropdown" data-dropdown>
                            <button type="button" class="nav-user" aria-haspopup="true" aria-expanded="false">
                                <span class="nav-avatar">{{ $initial }}</span>
                                {{ $user->name }}
                                <span class="role-badge">Admin</span>
                                <span class="nav-chevron" aria-hidden="true">@include('partials.icon', ['name' => 'chevron-down', 'size' => 14])</span>
                            </button>
                            <div class="nav-menu">
                                <div class="nav-menu-head">
                                    <strong>{{ $user->name }}</strong>
                                    <span>{{ $user->email }}</span>
                                </div>
                                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" {!! aria_current(request()->routeIs('admin.dashboard')) !!}>@include('partials.icon', ['name' => 'grid', 'size' => 16]) Dashboard Admin</a>
                                <a href="{{ route('admin.transactions.index') }}">@include('partials.icon', ['name' => 'receipt', 'size' => 16]) Verifikasi Pembayaran</a>
                                <a href="{{ route('admin.orders.index') }}">@include('partials.icon', ['name' => 'cart', 'size' => 16]) Pesanan Masuk</a>
                                <a href="{{ route('admin.reports.index') }}">@include('partials.icon', ['name' => 'grid', 'size' => 16]) Laporan Penjualan</a>
                                <div class="nav-menu-sep"></div>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="nav-menu-logout">@include('partials.icon', ['name' => 'logout', 'size' => 16]) Keluar</button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}" {!! aria_current(request()->routeIs('home')) !!}>Home</a>
                        <a href="{{ route('topup.index') }}" class="{{ request()->routeIs('topup.*') ? 'active' : '' }}" {!! aria_current(request()->routeIs('topup.*')) !!}>Top Up</a>
                        <a href="{{ route('marketplace.index') }}" class="{{ request()->routeIs('marketplace.*') && !request()->routeIs('marketplace.checkout*') && !request()->routeIs('marketplace.success') ? 'active' : '' }}" {!! aria_current(request()->routeIs('marketplace.*') && !request()->routeIs('marketplace.checkout*') && !request()->routeIs('marketplace.success')) !!}>Marketplace</a>
                        <a href="{{ route('cart.index') }}" class="nav-cart {{ request()->routeIs('cart.*') ? 'active' : '' }}" {!! aria_current(request()->routeIs('cart.*')) !!} aria-label="Keranjang belanja">
                            @include('partials.icon', ['name' => 'cart', 'size' => 19])
                            @if($cartCount > 0)<span class="cart-count">{{ $cartCount }}</span>@endif
                        </a>
                        <div class="nav-dropdown" data-dropdown>
                            <button type="button" class="nav-user" aria-haspopup="true" aria-expanded="false">
                                <span class="nav-avatar">{{ $initial }}</span>
                                {{ $user->name }}
                                <span class="nav-chevron" aria-hidden="true">@include('partials.icon', ['name' => 'chevron-down', 'size' => 14])</span>
                            </button>
                            <div class="nav-menu">
                                <div class="nav-menu-head">
                                    <strong>{{ $user->name }}</strong>
                                    <span>{{ $user->email }}</span>
                                </div>
                                <a href="{{ route('transactions.index') }}" class="{{ request()->routeIs('transactions.*') ? 'active' : '' }}" {!! aria_current(request()->routeIs('transactions.*')) !!}>@include('partials.icon', ['name' => 'receipt', 'size' => 16]) Riwayat Transaksi</a>
                                <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') ? 'active' : '' }}" {!! aria_current(request()->routeIs('orders.*')) !!}>@include('partials.icon', ['name' => 'cart', 'size' => 16]) Pesanan Saya</a>
                                <a href="{{ route('profile') }}" class="{{ request()->routeIs('profile') ? 'active' : '' }}" {!! aria_current(request()->routeIs('profile')) !!}>@include('partials.icon', ['name' => 'user', 'size' => 16]) Profil &amp; Pengaturan</a>
                                <div class="nav-menu-sep"></div>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="nav-menu-logout">@include('partials.icon', ['name' => 'logout', 'size' => 16]) Keluar</button>
                                </form>
                            </div>
                        </div>
                    @endif
                @else
                    <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}" {!! aria_current(request()->routeIs('home')) !!}>Home</a>
                    <a href="{{ route('topup.index') }}" class="{{ request()->routeIs('topup.*') ? 'active' : '' }}" {!! aria_current(request()->routeIs('topup.*')) !!}>Top Up</a>
                    <a href="{{ route('marketplace.index') }}" class="{{ request()->routeIs('marketplace.*') ? 'active' : '' }}" {!! aria_current(request()->routeIs('marketplace.*')) !!}>Marketplace</a>
                    <a href="{{ route('login') }}" class="{{ request()->routeIs('login') ? 'active' : '' }}" {!! aria_current(request()->routeIs('login')) !!}>Login</a>
                    <a href="{{ route('register') }}" class="{{ request()->routeIs('register') ? 'active' : '' }}" {!! aria_current(request()->routeIs('register')) !!}>
                        <span class="btn btn-primary btn-sm">Daftar</span>
                    </a>
                @endif
            </div>
        </div>
    </nav>

    <main id="main">
        <div class="container" style="padding-top:20px;">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-error">
                    <strong>Periksa kembali isian Anda:</strong>
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        @yield('content')
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-cols">
                <div class="footer-col">
                    <div class="footer-brand">
                        <span class="brand-logo">T</span>
                        <span class="footer-title">TopUpMarket</span>
                    </div>
                    <p class="muted" style="margin:0 0 14px;">
                        Top up game, voucher, skin, dan akun game — cepat, aman, dan transparan.
                    </p>
                    <div class="pill-row" style="margin-top:0;">
                        <span class="pill">QRIS</span>
                        <span class="pill">DANA</span>
                        <span class="pill">GoPay</span>
                        <span class="pill">OVO</span>
                        <span class="pill">Transfer Bank</span>
                    </div>
                </div>

                <div class="footer-col">
                    <h4>Layanan</h4>
                    <a href="{{ route('topup.index') }}">Top Up Game</a>
                    <a href="{{ route('marketplace.index') }}">Marketplace</a>
                    @auth
                        <a href="{{ route('cart.index') }}">Keranjang</a>
                    @endauth
                </div>

                <div class="footer-col">
                    <h4>Akun</h4>
                    @auth
                        <a href="{{ route('transactions.index') }}">Riwayat Transaksi</a>
                        <a href="{{ route('orders.index') }}">Pesanan Saya</a>
                        <a href="{{ route('profile') }}">Profil</a>
                    @else
                        <a href="{{ route('login') }}">Masuk</a>
                        <a href="{{ route('register') }}">Daftar</a>
                        <a href="{{ route('password.request') }}">Lupa Password</a>
                    @endauth
                </div>

                <div class="footer-col">
                    <h4>Bantuan</h4>
                    <a href="{{ route('page.how-to-order') }}">Cara Order</a>
                    <a href="{{ route('home') }}#faq">Pertanyaan Umum</a>
                    <a href="mailto:support@topupmarket.test">Hubungi Kami</a>
                </div>

                <div class="footer-col">
                    <h4>Kebijakan</h4>
                    <a href="{{ route('page.terms') }}">Syarat &amp; Ketentuan</a>
                    <a href="{{ route('page.privacy') }}">Kebijakan Privasi</a>
                    <a href="{{ route('sitemap') }}">Sitemap</a>
                </div>
            </div>

            <div class="footer-bottom">
                <span>&copy; {{ date('Y') }} TopUpMarket. Seluruh hak cipta dilindungi.</span>
                <span>Dibuat dengan <span style="color:var(--pink); display:inline-flex; vertical-align:-2px;">@include('partials.icon', ['name' => 'heart', 'size' => 13])</span> untuk para gamer Indonesia.</span>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            var toggle = document.getElementById('navToggle');
            var links = document.getElementById('navLinks');

            if (toggle && links) {
                var openIcon = toggle.querySelector('[data-icon="open"]');
                var closeIcon = toggle.querySelector('[data-icon="close"]');

                toggle.addEventListener('click', function () {
                    var open = links.classList.toggle('open');
                    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                    if (openIcon) openIcon.hidden = open;
                    if (closeIcon) closeIcon.hidden = !open;
                });
            }

            // Dropdown akun pengguna
            var dropdowns = document.querySelectorAll('[data-dropdown]');
            dropdowns.forEach(function (dd) {
                var trigger = dd.querySelector('.nav-user');
                if (!trigger) return;

                trigger.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var willOpen = !dd.classList.contains('open');
                    dropdowns.forEach(function (other) {
                        other.classList.remove('open');
                        var t = other.querySelector('.nav-user');
                        if (t) t.setAttribute('aria-expanded', 'false');
                    });
                    dd.classList.toggle('open', willOpen);
                    trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                });
            });

            document.addEventListener('click', function () {
                dropdowns.forEach(function (dd) {
                    dd.classList.remove('open');
                    var t = dd.querySelector('.nav-user');
                    if (t) t.setAttribute('aria-expanded', 'false');
                });
            });

            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') return;
                dropdowns.forEach(function (dd) {
                    dd.classList.remove('open');
                    var t = dd.querySelector('.nav-user');
                    if (t) t.setAttribute('aria-expanded', 'false');
                });
            });

            // Reveal on scroll
            var revealEls = document.querySelectorAll('.reveal');
            if (!revealEls.length) return;

            if (!('IntersectionObserver' in window)) {
                revealEls.forEach(function (el) { el.classList.add('in'); });
                return;
            }

            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('in');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

            revealEls.forEach(function (el) { observer.observe(el); });
        })();
    </script>

    @stack('scripts')
</body>
</html>
