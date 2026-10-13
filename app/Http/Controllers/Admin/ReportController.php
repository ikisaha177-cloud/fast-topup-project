<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Support\OrderStatus;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Laporan penjualan admin: harian, bulanan, dan tahunan.
 */
class ReportController extends Controller
{
    /** @var \App\Services\ReportService */
    protected $reports;

    public function __construct(ReportService $reports)
    {
        $this->reports = $reports;
    }

    /**
     * Halaman laporan (ringkasan + grafik + tabel per periode).
     */
    public function index(Request $request)
    {
        [$type, $year, $month, $status] = $this->filters($request);

        $report = $this->reports->build($type, $year, $month, $status);

        $report['periodLabel'] = $this->reports->periodLabel($type, $year, $month);

        return view('admin.reports.index', [
            'report'       => $report,
            'type'         => $type,
            'year'         => $year,
            'month'        => $month,
            'status'       => $status,
            'statusLabels' => OrderStatus::labels(),
        ]);
    }

    /**
     * Unduh laporan periode terpilih sebagai berkas CSV.
     */
    public function export(Request $request)
    {
        [$type, $year, $month, $status] = $this->filters($request);

        $report = $this->reports->build($type, $year, $month, $status);
        $label  = $this->reports->periodLabel($type, $year, $month);

        $filename = 'laporan-' . $type . '-' . $year
            . ($type === ReportService::TYPE_DAILY ? sprintf('-%02d', $month) : '')
            . '.csv';

        $headings = [
            'Periode',
            'Transaksi Top Up',
            'Pesanan Marketplace',
            'Pendapatan Top Up',
            'Pendapatan Marketplace',
            'Total Pendapatan',
        ];

        $rows = collect($report['rows'])->map(function ($row) {
            return [
                $row['label'],
                $row['topup_count'],
                $row['order_count'],
                $row['topup_revenue'],
                $row['market_revenue'],
                $row['revenue'],
            ];
        })->all();

        $summary = $report['summary'];

        return new StreamedResponse(function () use ($headings, $rows, $label, $summary, $status) {
            $handle = fopen('php://output', 'w');

            // BOM agar Excel membaca UTF-8 dengan benar.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Laporan Penjualan GantinoID Store']);
            fputcsv($handle, ['Periode', $label]);
            fputcsv($handle, ['Filter Status', $status === 'semua' ? 'Semua' : OrderStatus::label($status)]);
            fputcsv($handle, []);
            fputcsv($handle, $headings);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['TOTAL', $summary['topup_count'], $summary['order_count'], $summary['topup_revenue'], $summary['market_revenue'], $summary['revenue']]);

            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Ambil & normalisasi filter dari query string.
     *
     * @return array{0: string, 1: int, 2: int, 3: string}
     */
    private function filters(Request $request)
    {
        $type = (string) $request->query('periode', ReportService::TYPE_MONTHLY);

        if (! in_array($type, [ReportService::TYPE_DAILY, ReportService::TYPE_MONTHLY, ReportService::TYPE_YEARLY], true)) {
            $type = ReportService::TYPE_MONTHLY;
        }

        $year  = (int) $request->query('tahun', now()->year);
        $month = (int) $request->query('bulan', now()->month);

        if ($year < 2000 || $year > 2100) {
            $year = (int) now()->year;
        }

        if ($month < 1 || $month > 12) {
            $month = (int) now()->month;
        }

        $status = (string) $request->query('status', 'semua');

        if ($status !== 'semua' && ! in_array($status, OrderStatus::all(), true)) {
            $status = 'semua';
        }

        return [$type, $year, $month, $status];
    }
}
