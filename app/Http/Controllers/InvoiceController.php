<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrder;
use App\Models\Transaction;
use App\Support\OrderStatus;
use Illuminate\Http\Request;

/**
 * Halaman invoice pembayaran — satu halaman rapi & siap cetak/simpan PDF
 * untuk transaksi top up maupun pesanan marketplace.
 *
 * Invoice inilah yang boleh di-screenshot pembeli lalu diunggah sebagai
 * bukti pembayaran saat menekan "Saya Sudah Bayar".
 */
class InvoiceController extends Controller
{
    public function show(Request $request, $code)
    {
        $transaction = Transaction::where('transaction_code', $code)
            ->with(['gameTitle', 'product', 'user'])
            ->first();

        if ($transaction) {
            $this->authorize('view', $transaction);

            return view('invoice.show', [
                'doc'  => $this->transactionDocument($transaction),
                'back' => route('topup.success', $transaction->transaction_code),
            ]);
        }

        $order = MarketplaceOrder::where('order_code', $code)
            ->with(['items.product', 'buyer'])
            ->firstOrFail();

        $this->authorize('view', $order);

        return view('invoice.show', [
            'doc'  => $this->orderDocument($order),
            'back' => route('marketplace.success', $order->order_code),
        ]);
    }

    /**
     * Susun data invoice dari transaksi top up.
     *
     * @return array<string, mixed>
     */
    private function transactionDocument(Transaction $transaction)
    {
        return [
            'type'      => 'topup',
            'number'    => $transaction->transaction_code,
            'date'      => $transaction->created_at,
            'status'    => $transaction->status,
            'badge'     => $transaction->statusBadge(),
            'label'     => $transaction->statusLabel(),
            'paid'      => $transaction->isSuccess(),
            'paid_at'   => $transaction->confirmed_at ?: $transaction->paid_at,
            'method'    => $transaction->payment_method,
            'provider'  => $transaction->payment_provider,
            'reference' => $transaction->payment_reference,
            'buyer'     => [
                'name'  => $transaction->user->name ?? 'Guest',
                'email' => $transaction->user->email ?? null,
            ],
            'meta'      => array_filter([
                'User ID'   => $transaction->user_game_id,
                'Server ID' => $transaction->server_id,
            ], function ($value) {
                return $value !== null && $value !== '';
            }),
            'items'     => [[
                'name'     => $transaction->gameTitle->name ?? 'Top Up',
                'sub'      => $transaction->product->name ?? '-',
                'qty'      => 1,
                // Harga satuan tetap harga katalog; potongan promo ditampilkan terpisah.
                'price'    => (int) $transaction->amount + (int) $transaction->discount_amount,
                'subtotal' => (int) $transaction->amount + (int) $transaction->discount_amount,
            ]],
            'promo_code' => $transaction->promo_code,
            'discount'   => (int) $transaction->discount_amount,
            'total'     => (int) $transaction->amount,
        ];
    }

    /**
     * Susun data invoice dari pesanan marketplace.
     *
     * @return array<string, mixed>
     */
    private function orderDocument(MarketplaceOrder $order)
    {
        $items = [];

        foreach ($order->items as $item) {
            $items[] = [
                'name'     => $item->product->name ?? 'Item',
                'sub'      => $item->product->category ?? 'Marketplace',
                'qty'      => (int) $item->quantity,
                'price'    => (int) $item->price,
                'subtotal' => (int) $item->subtotal,
            ];
        }

        return [
            'type'      => 'marketplace',
            'number'    => $order->order_code,
            'date'      => $order->created_at,
            'status'    => $order->status,
            'badge'     => $order->statusBadge(),
            'label'     => $order->statusLabel(),
            'paid'      => $order->isSuccess(),
            'paid_at'   => $order->confirmed_at ?: $order->paid_at,
            'method'    => $order->payment_method,
            'provider'  => $order->payment_provider,
            'reference' => $order->payment_reference,
            'buyer'     => [
                'name'  => $order->buyer->name ?? 'Guest',
                'email' => $order->buyer->email ?? null,
            ],
            'meta'      => [],
            'items'     => $items,
            'promo_code' => $order->promo_code,
            'discount'   => (int) $order->discount_amount,
            'total'     => (int) $order->total_amount,
        ];
    }
}
