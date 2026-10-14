<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0b0a10">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <title>@yield('title', 'Portal Pembayaran - GantinoID Pay')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ config('app.asset_version') }}">
    @stack('styles')
</head>
<body class="gateway-body">
    @php
        // Host yang menampung portal — ditampilkan sebagai identitas "situs
        // pembayaran" agar pembeli tahu ia sedang di luar toko.
        $gatewayHost = parse_url(config('app.url'), PHP_URL_HOST) ?: request()->getHost();
    @endphp

    <header class="gateway-topbar">
        <div class="gateway-topbar-inner">
            <span class="gateway-brand">
                <img class="brand-logo" src="{{ asset('images/logo-mark.svg') }}" alt="" width="34" height="34">
                <span class="gateway-brand-text">
                    <strong>GantinoID Pay</strong>
                    <span>Portal Pembayaran</span>
                </span>
            </span>
            <span class="gateway-secure">
                @include('partials.icon', ['name' => 'shield', 'size' => 15])
                <span>Koneksi aman</span>
            </span>
        </div>
    </header>

    <main class="gateway-main">
        <div class="container">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            @yield('content')
        </div>
    </main>

    <footer class="gateway-footer">
        <div class="container">
            <p class="gateway-sim">
                @include('partials.icon', ['name' => 'shield', 'size' => 14])
                <span>Portal pembayaran <strong>simulasi</strong> — tidak ada uang yang benar-benar berpindah. Tidak ada data kartu yang diminta.</span>
            </p>
            <p class="gateway-host">
                <span class="mono">{{ $gatewayHost }}</span>
                @yield('gateway_back')
            </p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
