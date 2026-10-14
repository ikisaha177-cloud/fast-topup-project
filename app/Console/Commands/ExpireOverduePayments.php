<?php

namespace App\Console\Commands;

use App\Services\MarketplaceOrderService;
use App\Services\TransactionService;
use Illuminate\Console\Command;

/**
 * Batalkan transaksi & pesanan yang melewati batas waktu pembayaran.
 *
 * Sebelum ini pembatalan hanya terjadi "malas" saat halaman terkait dibuka.
 * Bila toko sepi berhari-hari, stok yang sudah dikunci tidak kembali dan
 * notifikasi batal tidak pernah dikirim. Command ini dijalankan terjadwal
 * (lihat App\Console\Kernel) sehingga pemulihan stok berjalan tanpa menunggu
 * ada pengunjung.
 */
class ExpireOverduePayments extends Command
{
    /** @var string */
    protected $signature = 'payments:expire';

    /** @var string */
    protected $description = 'Batalkan transaksi/pesanan yang lewat batas waktu pembayaran dan kembalikan stok';

    /** @var \App\Services\TransactionService */
    protected $transactions;

    /** @var \App\Services\MarketplaceOrderService */
    protected $orders;

    public function __construct(TransactionService $transactions, MarketplaceOrderService $orders)
    {
        parent::__construct();

        $this->transactions = $transactions;
        $this->orders = $orders;
    }

    /**
     * @return int
     */
    public function handle()
    {
        $transactions = $this->transactions->expireOverdue();
        $orders = $this->orders->expireOverdue();

        $this->info(sprintf(
            'Kedaluwarsa diproses: %d transaksi top up, %d pesanan marketplace.',
            $transactions,
            $orders
        ));

        return 0;
    }
}
