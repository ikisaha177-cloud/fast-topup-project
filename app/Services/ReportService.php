<?php

namespace App\Services;

use App\Models\MarketplaceOrder;
use App\Models\Transaction;
use App\Support\OrderStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Mesin laporan penjualan: harian, bulanan, dan tahunan.
 *
 * Semua agregasi dihitung di PHP setelah data dibatasi rentang waktunya,
 * sehingga hasilnya identik di MySQL (produksi) maupun SQLite (test) tanpa
 * bergantung pada fungsi tanggal yang berbeda antar database.
 */
class ReportService
{
    public const TYPE_DAILY   = 'harian';
    public const TYPE_MONTHLY = 'bulanan';
    public const TYPE_YEARLY  = 'tahunan';

    /** @var array<int, string> */
    private $months = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];

    /**
     * Bangun laporan lengkap untuk tipe & periode terpilih.
     *
     * @param  string  $type   harian|bulanan|tahunan
     * @param  int     $year
     * @param  int     $month  1-12 (dipakai untuk tipe harian)
     * @param  string  $status semua|pending|success|failed|cancelled
     * @return array<string, mixed>
     */
    public function build($type, $year, $month, $status = 'semua')
    {
        [$start, $end, $buckets] = $this->range($type, $year, $month);

        $transactions = Transaction::with(['gameTitle', 'product'])
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $orders = MarketplaceOrder::with(['items.product'])
            ->whereBetween('created_at', [$start, $end])
            ->get();

        // Sebaran status dihitung tanpa filter supaya admin tetap melihat gambaran penuh.
        $statusBreakdown = $this->statusBreakdown($transactions, $orders);

        $filtered = $this->applyStatusFilter($transactions, $orders, $status);

        $rows = $this->bucketize($type, $buckets, $filtered['transactions'], $filtered['orders']);

        return [
            'type'            => $type,
            'year'            => (int) $year,
            'month'           => (int) $month,
            'status'          => $status,
            'start'           => $start,
            'end'             => $end,
            'rows'            => $rows,
            'summary'         => $this->summarize($filtered['transactions'], $filtered['orders']),
            'statusBreakdown' => $statusBreakdown,
            'topGames'        => $this->topGames($filtered['transactions']),
            'topProducts'     => $this->topProducts($filtered['orders']),
            'paymentMethods'  => $this->paymentMethods($filtered['transactions'], $filtered['orders']),
            'peak'            => max(1, (int) collect($rows)->max('revenue')),
        ];
    }

    /**
     * Label periode yang enak dibaca, mis. "Oktober 2026".
     */
    public function periodLabel($type, $year, $month)
    {
        $fullMonths = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        if ($type === self::TYPE_YEARLY) {
            return 'Tahun ' . $year;
        }

        if ($type === self::TYPE_MONTHLY) {
            return 'Tahun ' . $year;
        }

        return ($fullMonths[(int) $month] ?? '-') . ' ' . $year;
    }

    /**
     * Tentukan rentang waktu + kerangka bucket untuk tiap tipe laporan.
     *
     * @return array{0: Carbon, 1: Carbon, 2: array<string, array<string, mixed>>}
     */
    private function range($type, $year, $month)
    {
        $year  = (int) $year;
        $month = max(1, min(12, (int) $month));

        if ($type === self::TYPE_DAILY) {
            $start = Carbon::create($year, $month, 1)->startOfDay();
            $end   = (clone $start)->endOfMonth()->endOfDay();

            $buckets = [];

            for ($day = 1; $day <= $start->daysInMonth; $day++) {
                $date = Carbon::create($year, $month, $day);

                $buckets[$date->toDateString()] = [
                    'key'   => $date->toDateString(),
                    'label' => $date->format('d') . ' ' . $this->months[$month],
                ];
            }

            return [$start, $end, $buckets];
        }

        if ($type === self::TYPE_MONTHLY) {
            $start = Carbon::create($year, 1, 1)->startOfDay();
            $end   = (clone $start)->endOfYear()->endOfDay();

            $buckets = [];

            for ($m = 1; $m <= 12; $m++) {
                $buckets[sprintf('%04d-%02d', $year, $m)] = [
                    'key'   => sprintf('%04d-%02d', $year, $m),
                    'label' => $this->months[$m],
                ];
            }

            return [$start, $end, $buckets];
        }

        // Tahunan: dari tahun paling awal yang punya data sampai tahun berjalan.
        $earliest = $this->earliestYear();
        $lastYear = max((int) now()->year, $year);

        $start = Carbon::create($earliest, 1, 1)->startOfDay();
        $end   = Carbon::create($lastYear, 12, 31)->endOfDay();

        $buckets = [];

        for ($y = $earliest; $y <= $lastYear; $y++) {
            $buckets[(string) $y] = [
                'key'   => (string) $y,
                'label' => (string) $y,
            ];
        }

        return [$start, $end, $buckets];
    }

    /**
     * Tahun paling awal yang memiliki transaksi atau pesanan.
     */
    private function earliestYear()
    {
        $years = [];

        $txn = Transaction::min('created_at');
        if ($txn) {
            $years[] = (int) Carbon::parse($txn)->year;
        }

        $order = MarketplaceOrder::min('created_at');
        if ($order) {
            $years[] = (int) Carbon::parse($order)->year;
        }

        return $years ? min($years) : (int) now()->year;
    }

    /**
     * Saring transaksi & pesanan sesuai filter status.
     *
     * @return array{transactions: Collection, orders: Collection}
     */
    private function applyStatusFilter(Collection $transactions, Collection $orders, $status)
    {
        if (! in_array($status, OrderStatus::all(), true)) {
            return ['transactions' => $transactions, 'orders' => $orders];
        }

        return [
            'transactions' => $transactions->where('status', $status)->values(),
            'orders'       => $orders->where('status', $status)->values(),
        ];
    }

    /**
     * Kelompokkan pendapatan & jumlah pesanan ke dalam bucket periode.
     *
     * @param  string                               $type
     * @param  array<string, array<string, mixed>>  $buckets
     * @return array<int, array<string, mixed>>
     */
    private function bucketize($type, array $buckets, Collection $transactions, Collection $orders)
    {
        foreach ($buckets as $key => $meta) {
            $buckets[$key] = $meta + [
                'topup_revenue' => 0,
                'market_revenue' => 0,
                'revenue'       => 0,
                'topup_count'   => 0,
                'order_count'   => 0,
            ];
        }

        foreach ($transactions as $transaction) {
            $key = $this->bucketKey($type, $transaction->created_at);

            if (! isset($buckets[$key])) {
                continue;
            }

            $buckets[$key]['topup_count']++;

            if ($transaction->status === OrderStatus::SUCCESS) {
                $buckets[$key]['topup_revenue'] += (int) $transaction->amount;
            }
        }

        foreach ($orders as $order) {
            $key = $this->bucketKey($type, $order->created_at);

            if (! isset($buckets[$key])) {
                continue;
            }

            $buckets[$key]['order_count']++;

            if ($order->status === OrderStatus::SUCCESS) {
                $buckets[$key]['market_revenue'] += (int) $order->total_amount;
            }
        }

        foreach ($buckets as $key => $bucket) {
            $buckets[$key]['revenue'] = $bucket['topup_revenue'] + $bucket['market_revenue'];
        }

        return array_values($buckets);
    }

    /**
     * Kunci bucket sesuai tipe laporan.
     */
    private function bucketKey($type, $date)
    {
        $date = Carbon::parse($date);

        if ($type === self::TYPE_DAILY) {
            return $date->toDateString();
        }

        if ($type === self::TYPE_MONTHLY) {
            return $date->format('Y-m');
        }

        return $date->format('Y');
    }

    /**
     * Ringkasan total untuk seluruh periode terpilih.
     *
     * @return array<string, mixed>
     */
    private function summarize(Collection $transactions, Collection $orders)
    {
        $topupRevenue  = (int) $transactions->where('status', OrderStatus::SUCCESS)->sum('amount');
        $marketRevenue = (int) $orders->where('status', OrderStatus::SUCCESS)->sum('total_amount');
        $successCount  = $transactions->where('status', OrderStatus::SUCCESS)->count()
            + $orders->where('status', OrderStatus::SUCCESS)->count();

        return [
            'topup_revenue'  => $topupRevenue,
            'market_revenue' => $marketRevenue,
            'revenue'        => $topupRevenue + $marketRevenue,
            'topup_count'    => $transactions->count(),
            'order_count'    => $orders->count(),
            'total_count'    => $transactions->count() + $orders->count(),
            'success_count'  => $successCount,
            'average'        => $successCount > 0 ? (int) round(($topupRevenue + $marketRevenue) / $successCount) : 0,
        ];
    }

    /**
     * Jumlah transaksi/pesanan per status untuk periode terpilih.
     *
     * @return array<string, array{label: string, count: int, amount: int}>
     */
    private function statusBreakdown(Collection $transactions, Collection $orders)
    {
        $breakdown = [];

        foreach (OrderStatus::all() as $status) {
            $txn   = $transactions->where('status', $status);
            $order = $orders->where('status', $status);

            $breakdown[$status] = [
                'label'  => OrderStatus::label($status),
                'count'  => $txn->count() + $order->count(),
                'amount' => (int) $txn->sum('amount') + (int) $order->sum('total_amount'),
            ];
        }

        return $breakdown;
    }

    /**
     * Game paling laris — hanya menghitung pesanan yang benar-benar selesai,
     * supaya jumlahnya konsisten dengan angka pendapatan.
     *
     * @return array<int, array{name: string, count: int, revenue: int}>
     */
    private function topGames(Collection $transactions)
    {
        return $transactions
            ->where('status', OrderStatus::SUCCESS)
            ->groupBy(function ($transaction) {
                return $transaction->gameTitle->name ?? 'Tanpa Game';
            })
            ->map(function (Collection $group, $name) {
                return [
                    'name'    => $name,
                    'count'   => $group->count(),
                    'revenue' => (int) $group->sum('amount'),
                ];
            })
            ->sortByDesc('count')
            ->take(5)
            ->values()
            ->all();
    }

    /**
     * Produk marketplace paling laris — hanya pesanan selesai, agar
     * "Terjual" dan "Pendapatan" berasal dari himpunan data yang sama.
     *
     * @return array<int, array{name: string, quantity: int, revenue: int}>
     */
    private function topProducts(Collection $orders)
    {
        $rows = [];

        foreach ($orders->where('status', OrderStatus::SUCCESS) as $order) {
            foreach ($order->items as $item) {
                $name = $item->product->name ?? 'Produk Dihapus';

                if (! isset($rows[$name])) {
                    $rows[$name] = ['name' => $name, 'quantity' => 0, 'revenue' => 0];
                }

                $rows[$name]['quantity'] += (int) $item->quantity;
                $rows[$name]['revenue']  += (int) $item->subtotal;
            }
        }

        usort($rows, function ($a, $b) {
            return $b['quantity'] <=> $a['quantity'];
        });

        return array_slice($rows, 0, 5);
    }

    /**
     * Ringkasan pendapatan per metode pembayaran (hanya yang berhasil).
     *
     * @return array<int, array{method: string, count: int, revenue: int}>
     */
    private function paymentMethods(Collection $transactions, Collection $orders)
    {
        $rows = [];

        foreach ($transactions as $transaction) {
            $this->accumulateMethod($rows, $transaction->payment_method, $transaction->amount, $transaction->status === OrderStatus::SUCCESS);
        }

        foreach ($orders as $order) {
            $this->accumulateMethod($rows, $order->payment_method, $order->total_amount, $order->status === OrderStatus::SUCCESS);
        }

        usort($rows, function ($a, $b) {
            return $b['revenue'] <=> $a['revenue'];
        });

        return $rows;
    }

    /**
     * @param  array<string, array{method: string, count: int, revenue: int}>  $rows
     */
    private function accumulateMethod(array &$rows, $method, $amount, $isSuccess)
    {
        $method = $method ?: 'Lainnya';

        if (! isset($rows[$method])) {
            $rows[$method] = ['method' => $method, 'count' => 0, 'revenue' => 0];
        }

        $rows[$method]['count']++;

        if ($isSuccess) {
            $rows[$method]['revenue'] += (int) $amount;
        }
    }
}
