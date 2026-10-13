@php
    $legalTabs = [
        ['route' => 'page.how-to-order', 'label' => 'Cara Order'],
        ['route' => 'page.terms',        'label' => 'Syarat & Ketentuan'],
        ['route' => 'page.privacy',      'label' => 'Kebijakan Privasi'],
    ];
@endphp

<nav class="legal-tabs" aria-label="Halaman kebijakan">
    @foreach($legalTabs as $tab)
        <a href="{{ route($tab['route']) }}"
           class="chip {{ request()->routeIs($tab['route']) ? 'active' : '' }}"
           {!! aria_current(request()->routeIs($tab['route']), 'page') !!}>{{ $tab['label'] }}</a>
    @endforeach
</nav>
