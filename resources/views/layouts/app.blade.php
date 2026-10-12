<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="TopUpMarket — top up game instan dan marketplace voucher, skin, dan akun game dengan pembayaran QRIS, e-wallet, dan transfer bank.">
    <title>@yield('title', 'TopUpMarket — Top Up Game & Marketplace')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <script>document.documentElement.classList.add('js');</script>
</head>
<body>
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

            <button type="button" class="nav-toggle" id="navToggle" aria-label="Buka menu" aria-expanded="false">☰</button>

            <div class="nav-links" id="navLinks">
                @if($user)
                    @if($isAdmin)
                        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.*') ? 'active' : '' }}">Dashboard Admin</a>
                        <a href="{{ route('home') }}">Lihat Toko</a>
                        <span class="nav-user">
                            <span class="nav-avatar">{{ $initial }}</span>
                            {{ $user->name }}
                            <span class="role-badge">Admin</span>
                        </span>
                        <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-sm">Logout</button>
                        </form>
                    @else
                        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a>
                        <a href="{{ route('topup.index') }}" class="{{ request()->routeIs('topup.*') ? 'active' : '' }}">Top Up</a>
                        <a href="{{ route('marketplace.index') }}" class="{{ request()->routeIs('marketplace.*') ? 'active' : '' }}">Marketplace</a>
                        <a href="{{ route('cart.index') }}" class="{{ request()->routeIs('cart.*') ? 'active' : '' }}">
                            Keranjang @if($cartCount > 0)<span class="cart-count">{{ $cartCount }}</span>@endif
                        </a>
                        <a href="{{ route('transactions.index') }}" class="{{ request()->routeIs('transactions.*') ? 'active' : '' }}">Transaksi</a>
                        <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') ? 'active' : '' }}">Pesanan</a>
                        <a href="{{ route('profile') }}" class="{{ request()->routeIs('profile') ? 'active' : '' }}">Profile</a>
                        <span class="nav-user">
                            <span class="nav-avatar">{{ $initial }}</span>
                            {{ $user->name }}
                        </span>
                        <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-sm">Logout</button>
                        </form>
                    @endif
                @else
                    <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a>
                    <a href="{{ route('topup.index') }}" class="{{ request()->routeIs('topup.*') ? 'active' : '' }}">Top Up</a>
                    <a href="{{ route('marketplace.index') }}" class="{{ request()->routeIs('marketplace.*') ? 'active' : '' }}">Marketplace</a>
                    <a href="{{ route('login') }}" class="{{ request()->routeIs('login') ? 'active' : '' }}">Login</a>
                    <a href="{{ route('register') }}" class="{{ request()->routeIs('register') ? 'active' : '' }}">
                        <span class="btn btn-primary btn-sm">Daftar Gratis</span>
                    </a>
                @endif
            </div>
        </div>
    </nav>

    <main>
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
                        Top up game instan, voucher, skin, dan akun game — cepat, aman, dan transparan.
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
                        <a href="{{ route('register') }}">Daftar Gratis</a>
                        <a href="{{ route('password.request') }}">Lupa Password</a>
                    @endauth
                </div>

                <div class="footer-col">
                    <h4>Bantuan</h4>
                    <a href="{{ route('home') }}#faq">Pertanyaan Umum</a>
                    <a href="{{ route('marketplace.index') }}">Cara Belanja</a>
                    <a href="{{ route('topup.index') }}">Cara Top Up</a>
                </div>
            </div>

            <div class="footer-bottom">
                <span>&copy; {{ date('Y') }} TopUpMarket. Seluruh hak cipta dilindungi.</span>
                <span>Dibuat dengan <span style="color:var(--pink)">♥</span> untuk para gamer Indonesia.</span>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            var toggle = document.getElementById('navToggle');
            var links = document.getElementById('navLinks');

            if (toggle && links) {
                toggle.addEventListener('click', function () {
                    var open = links.classList.toggle('open');
                    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                    toggle.textContent = open ? '✕' : '☰';
                });
            }

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
