<?php

namespace App\Services\Payment\Drivers;

use App\Services\Payment\PaymentCharge;
use App\Services\Payment\PaymentGateway;
use App\Services\Payment\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Gateway simulasi: tidak memanggil jaringan sama sekali.
 *
 * Dipakai untuk demo & testing sehingga alur checkout -> bayar -> selesai
 * tetap bisa dijalankan tanpa akun payment provider.
 *
 * Webhook-nya tetap diverifikasi memakai HMAC agar alur idempotency yang
 * sama dengan gateway sungguhan bisa diuji sejak sekarang.
 */
class SimulationGateway implements PaymentGateway
{
    /** @var string */
    protected $secret;

    public function __construct(?string $secret = null)
    {
        $this->secret = (string) ($secret ?: config('app.key'));
    }

    public function name(): string
    {
        return 'simulation';
    }

    public function createCharge(string $reference, int $amount, string $method, array $context = []): PaymentCharge
    {
        // Kode QR palsu: meniru payload QRIS, cukup untuk ditampilkan sebagai QR.
        $qr = 'SIM-' . strtoupper(Str::random(24));

        return new PaymentCharge(
            $reference,
            $amount,
            $method,
            'pending',
            null,
            $qr,
            'SIM-' . Str::upper(Str::random(10)),
            ['driver' => 'simulation']
        );
    }

    public function parseWebhook(Request $request): WebhookEvent
    {
        $payload = $request->all();

        $reference = (string) ($payload['order_id'] ?? '');
        $providerStatus = (string) ($payload['transaction_status'] ?? 'pending');

        $signature = (string) $request->header('X-Simulation-Signature', '');
        $expected = hash_hmac('sha256', $reference . '|' . $providerStatus, $this->secret);

        if (! hash_equals($expected, $signature)) {
            throw new \App\Services\Payment\Exceptions\InvalidWebhookSignature(
                'Signature webhook simulasi tidak valid.'
            );
        }

        return new WebhookEvent(
            (string) ($payload['event_id'] ?? $reference . ':' . $providerStatus),
            $reference,
            $this->mapStatus($providerStatus),
            $providerStatus,
            isset($payload['gross_amount']) ? (int) $payload['gross_amount'] : null,
            $payload['payment_type'] ?? null,
            $payload
        );
    }

    /**
     * Petakan status provider ke status internal kita.
     */
    protected function mapStatus(string $providerStatus): string
    {
        return [
            'capture'    => 'success',
            'settlement' => 'success',
            'pending'    => 'pending',
            'expire'     => 'cancelled',
            'cancel'     => 'cancelled',
            'deny'       => 'failed',
            'failure'    => 'failed',
        ][$providerStatus] ?? 'pending';
    }
}
