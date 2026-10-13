@extends('layouts.app')

@php
    /**
     * Halaman invoice — satu dokumen untuk transaksi top up & pesanan
     * marketplace. $doc disusun oleh InvoiceController.
     */
    $isPaid = ! empty($doc['paid']);
@endphp

@section('title', 'Invoice ' . $doc['number'] . ' - TopUpMarket')

@push('styles')
<style>
    /* Invoice dicetak/di-screenshot di atas kertas putih agar hasilnya rapi
       dan kontras — terlepas dari tema gelap aplikasi. */
    .invoice-wrap { max-width: 820px; margin: 0 auto; }

    .invoice-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-end;
        margin-bottom: 16px;
    }

    .invoice-doc {
        position: relative;
        background: #ffffff;
        color: #1a1a22;
        border-radius: var(--radius);
        padding: 34px 34px 26px;
        box-shadow: 0 30px 70px rgba(0, 0, 0, 0.45);
        overflow: hidden;
    }

    .invoice-doc::before {
        content: "";
        position: absolute;
        inset: 0 0 auto 0;
        height: 6px;
        background: linear-gradient(90deg, #ffd60a, #f0b90b 45%, #ff7a18);
    }

    .invoice-head {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        flex-wrap: wrap;
        padding-bottom: 20px;
        border-bottom: 1px solid #e6e6ec;
    }

    .invoice-brand {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .invoice-brand-mark {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: linear-gradient(135deg, #ffd60a, #f0b90b);
        color: #14110a;
        display: grid;
        place-items: center;
        font-weight: 900;
        font-size: 20px;
    }

    .invoice-brand-name {
        font-family: var(--font-display);
        font-weight: 800;
        font-size: 20px;
        letter-spacing: -0.02em;
        color: #14110a;
        line-height: 1.1;
    }

    .invoice-brand-sub {
        font-size: 12px;
        color: #6b6b7b;
    }

    .invoice-title {
        text-align: right;
    }

    .invoice-title h1 {
        font-family: var(--font-display);
        font-size: 26px;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        margin: 0;
        color: #14110a;
    }

    .invoice-title .inv-number {
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 14px;
        color: #4a4a5a;
    }

    .invoice-stamp {
        display: inline-block;
        margin-top: 10px;
        padding: 6px 14px;
        border-radius: 999px;
        font-weight: 800;
        font-size: 12px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        border: 2px solid currentColor;
    }

    .invoice-stamp.is-paid { color: #1f8a3b; }
    .invoice-stamp.is-pending { color: #b6791a; }
    .invoice-stamp.is-failed { color: #b3261e; }

    .invoice-meta {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 18px;
        padding: 20px 0;
        border-bottom: 1px solid #e6e6ec;
    }

    .invoice-meta > div { min-width: 0; }

    .invoice-meta h3 {
        font-size: 11px;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #8a8a99;
        margin: 0 0 6px;
    }

    .invoice-meta p {
        margin: 0;
        font-size: 14px;
        color: #1a1a22;
        line-height: 1.5;
    }

    .invoice-meta .mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 13px;
    }

    table.invoice-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        margin: 22px 0 8px;
    }

    table.invoice-table th {
        text-align: left;
        font-size: 11px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #8a8a99;
        padding: 0 0 10px;
        border-bottom: 1px solid #e6e6ec;
    }

    table.invoice-table th:first-child,
    table.invoice-table td:first-child { width: 46%; }

    table.invoice-table th:last-child,
    table.invoice-table td:last-child { text-align: right; }

    table.invoice-table td {
        padding: 12px 0;
        font-size: 14px;
        color: #1a1a22;
        border-bottom: 1px solid #f0f0f4;
        vertical-align: top;
        overflow-wrap: anywhere;
    }

    table.invoice-table td .item-sub {
        display: block;
        font-size: 12px;
        color: #7a7a89;
        margin-top: 2px;
    }

    .invoice-total {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        margin-top: 14px;
        padding-top: 16px;
        border-top: 2px solid #14110a;
    }

    .invoice-total span:first-child {
        font-size: 13px;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #6b6b7b;
    }

    .invoice-total .amount {
        font-family: var(--font-display);
        font-size: 26px;
        font-weight: 800;
        color: #14110a;
        font-variant-numeric: tabular-nums;
    }

    .invoice-pay {
        display: flex;
        gap: 22px;
        align-items: center;
        flex-wrap: wrap;
        margin-top: 24px;
        padding: 18px;
        background: #f7f7fb;
        border-radius: 14px;
    }

    .invoice-qr {
        width: 132px;
        height: 132px;
        padding: 10px;
        background: #fff;
        border: 1px solid #e2e2ea;
        border-radius: 12px;
        flex: 0 0 auto;
    }

    .invoice-qr svg { width: 100%; height: 100%; display: block; }

    .invoice-pay-info { flex: 1 1 220px; }
    .invoice-pay-info p { margin: 0 0 6px; font-size: 13.5px; color: #33333f; }
    .invoice-pay-info strong { color: #14110a; }

    .invoice-foot {
        margin-top: 26px;
        padding-top: 16px;
        border-top: 1px dashed #d9d9e2;
        font-size: 12px;
        color: #7a7a89;
        line-height: 1.6;
    }

    @media (max-width: 640px) {
        .invoice-doc { padding: 24px 20px; }
        .invoice-title { text-align: left; }
        .invoice-title h1 { font-size: 22px; }
        .invoice-actions { justify-content: flex-start; }
        .invoice-meta { grid-template-columns: 1fr; }

        /* Sederhanakan tabel: sisakan deskripsi + subtotal agar tidak meluber. */
        .invoice-table th:nth-child(2),
        .invoice-table td:nth-child(2),
        .invoice-table th:nth-child(3),
        .invoice-table td:nth-child(3) {
            display: none;
        }

        .invoice-table td,
        .invoice-table th { font-size: 13px; }

        /* Kolom pembayaran ditumpuk agar QR & teks tidak berebut lebar. */
        .invoice-pay {
            flex-direction: column;
            align-items: flex-start;
            gap: 14px;
        }

        .invoice-pay-info { min-width: 0; width: 100%; }
    }
</style>
@endpush

@section('content')
<section class="section">
    <div class="container">
        <div class="invoice-wrap">
            <div class="invoice-actions">
                <a href="{{ $back }}" class="btn btn-ghost">Kembali</a>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    @include('partials.icon', ['name' => 'download', 'size' => 16]) Cetak / Simpan PDF
                </button>
            </div>

            <article class="invoice-doc">
                <div class="invoice-head">
                    <div class="invoice-brand">
                        <span class="invoice-brand-mark" aria-hidden="true">T</span>
                        <span>
                            <span class="invoice-brand-name">TopUpMarket</span><br>
                            <span class="invoice-brand-sub">Invoice pembayaran resmi</span>
                        </span>
                    </div>

                    <div class="invoice-title">
                        <h1>Invoice</h1>
                        <span class="inv-number">{{ $doc['number'] }}</span>
                        <div>
                            <span class="invoice-stamp {{ $isPaid ? 'is-paid' : ($doc['status'] === 'pending' ? 'is-pending' : 'is-failed') }}">
                                {{ $isPaid ? 'Lunas' : $doc['label'] }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="invoice-meta">
                    <div>
                        <h3>Ditagihkan kepada</h3>
                        <p>
                            {{ $doc['buyer']['name'] }}<br>
                            @if($doc['buyer']['email'])
                                <span class="mono">{{ $doc['buyer']['email'] }}</span>
                            @endif
                        </p>
                    </div>

                    <div>
                        <h3>Tanggal</h3>
                        <p>{{ $doc['date']->format('d-m-Y H:i') }} WIB</p>
                    </div>

                    <div>
                        <h3>Metode Pembayaran</h3>
                        <p>{{ $doc['method'] }}@if($doc['provider']) &middot; {{ $doc['provider'] }}@endif</p>
                    </div>

                    @if(! empty($doc['meta']))
                        <div>
                            <h3>Detail Pesanan</h3>
                            <p>
                                @foreach($doc['meta'] as $label => $value)
                                    {{ $label }}: <span class="mono">{{ $value }}</span>@if(! $loop->last)<br>@endif
                                @endforeach
                            </p>
                        </div>
                    @endif
                </div>

                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>Deskripsi</th>
                            <th>Qty</th>
                            <th>Harga</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($doc['items'] as $item)
                            <tr>
                                <td>
                                    {{ $item['name'] }}
                                    <span class="item-sub">{{ $item['sub'] }}</span>
                                </td>
                                <td>{{ $item['qty'] }}</td>
                                <td>Rp {{ number_format($item['price'], 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="invoice-total">
                    <span>Total Tagihan</span>
                    <span class="amount">Rp {{ number_format($doc['total'], 0, ',', '.') }}</span>
                </div>

                <div class="invoice-pay">
                    <div class="invoice-qr">
                        @include('partials.fake-qr', ['code' => $doc['number']])
                    </div>
                    <div class="invoice-pay-info">
                        @if($isPaid)
                            <p><strong>Pembayaran diterima.</strong></p>
                            <p>Diverifikasi {{ $doc['paid_at'] ? $doc['paid_at']->format('d-m-Y H:i') : '-' }} WIB.
                                @if(! empty($doc['reference'])) Ref: <span class="mono">{{ $doc['reference'] }}</span>@endif
                            </p>
                            <p>Simpan invoice ini sebagai bukti pembayaran yang sah.</p>
                        @else
                            <p><strong>Selesaikan pembayaran {{ $doc['method'] }}.</strong></p>
                            <p>Scan QR di samping atau bayar sesuai kode <span class="mono">{{ $doc['number'] }}</span>
                                sebesar <strong>Rp {{ number_format($doc['total'], 0, ',', '.') }}</strong>.</p>
                            <p>Setelah membayar, unggah screenshot invoice ini sebagai bukti agar pesanan diproses.</p>
                        @endif
                    </div>
                </div>

                <div class="invoice-foot">
                    Invoice ini dibuat otomatis oleh sistem TopUpMarket dan sah tanpa tanda tangan.
                    Simpan sebagai bukti pembayaran. Pertanyaan? Hubungi tim dukungan kami.
                </div>
            </article>
        </div>
    </div>
</section>
@endsection
