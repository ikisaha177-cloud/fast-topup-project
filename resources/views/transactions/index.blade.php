@extends('layouts.app')

@section('title', 'Riwayat Transaksi - GantinoID Store')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div>
                <h1 class="section-title">Riwayat Transaksi</h1>
                <p class="section-subtitle mb-0">Semua transaksi top up milik akunmu.</p>
            </div>
        </div>

        @include('partials.account-tabs')

        @php
            $statusOptions = \App\Support\OrderStatus::labels();
            $badge = fn($s) => \App\Support\OrderStatus::badge($s);
            $label = fn($s) => \App\Support\OrderStatus::label($s);
        @endphp

        <div class="chips" style="margin-bottom:20px;">
            <a href="{{ route('transactions.index') }}" class="chip {{ $status ? '' : 'active' }}" {!! aria_current(! $status, 'true') !!}>Semua</a>
            @foreach($statusOptions as $value => $text)
                <a href="{{ route('transactions.index', ['status' => $value]) }}" class="chip {{ $status === $value ? 'active' : '' }}" {!! aria_current($status === $value, 'true') !!}>{{ $text }}</a>
            @endforeach
        </div>

        @if($transactions->isEmpty())
            <div class="empty">
                <span class="empty-icon">@include('partials.icon', ['name' => 'receipt', 'size' => 34])</span>
                Belum ada transaksi.
                <div class="mt-16">
                    <a href="{{ route('topup.index') }}" class="btn btn-primary">Mulai Top Up</a>
                </div>
            </div>
        @else
            <div class="card table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Kode</th>
                            <th scope="col">Game</th>
                            <th scope="col">Produk</th>
                            <th scope="col">User ID</th>
                            <th scope="col">Metode</th>
                            <th scope="col">Total</th>
                            <th scope="col">Status</th>
                            <th scope="col">Tanggal</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $trx)
                            <tr>
                                <td class="mono">{{ $trx->transaction_code }}</td>
                                <td>
                                    <div class="item-cell">
                                        @include('partials.game-icon', ['game' => $trx->gameTitle ?? (object) [], 'size' => 'xs'])
                                        <span class="item-name">{{ $trx->gameTitle->name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td>{{ $trx->product->name ?? '-' }}</td>
                                <td>{{ $trx->user_game_id }}</td>
                                <td>{{ $trx->payment_method }}</td>
                                <td>Rp {{ number_format($trx->amount, 0, ',', '.') }}</td>
                                <td><span class="badge {{ $badge($trx->status) }}">{{ $label($trx->status) }}</span></td>
                                <td>{{ $trx->created_at->format('d-m-Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('transactions.show', $trx->transaction_code) }}" class="btn btn-outline btn-sm">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center muted">Tidak ada transaksi yang cocok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $transactions->links() }}
        @endif
    </div>
</section>
@endsection
