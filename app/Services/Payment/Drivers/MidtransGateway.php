<?php

namespace App\Services\Payment\Drivers;

use App\Services\Payment\Exceptions\InvalidWebhookSignature;
use App\Services\Payment\PaymentCharge;
use App\Services\Payment\PaymentGateway;
use App\Services\Payment\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Driver Midtrans (Snap).
 *
 * Dokumentasi:
 * - Charge: POST {base_url}/snap/v1/transactions (Basic auth server key)
 * - Webhook signature: sha512(order_id + status_code + gross_amount + server_key)
 *
 * Driver ini hanya aktif bila PAYMENT_DRIVER=midtrans dan server key diisi.
 */
class MidtransGateway implements PaymentGateway
{
    /** @var array<string, mixed> */
    protected $config;

    public function __construct(array $config = [])
    {
        $this->config = $config ?: (array) config('payment.drivers.midtrans', []);
    }

    public function name(): string
    {
        return 'midtrans';
    }

    public function createCharge(string $reference, int $amount, string $method, array $context = []): PaymentCharge
    {
        $payload = [
            'transaction_details' => [
                'order_id'     => $reference,
                'gross_amount' => $amount,
            ],
            'enabled_payments' => $this->enabledPayments($method),
            'credit_card' => [
                'secure' => (bool) ($this->config['is_3ds'] ?? true),
            ],
            'callbacks' => [
                'finish' => $context['finish_url'] ?? null,
            ],
        ];

        if (! empty($context['customer'])) {
            $payload['customer_details'] = $context['customer'];
        }

        $response = Http::withBasicAuth((string) $this->config['server_key'], '')
            ->timeout((int) ($this->config['timeout'] ?? 15))
            ->acceptJson()
            ->post(rtrim((string) $this->config['base_url'], '/') . '/snap/v1/transactions', $payload);

        $data = $response->json() ?: [];

        return new PaymentCharge(
            $reference,
            $amount,
            $method,
            'pending',
            $data['redirect_url'] ?? null,
            null,
            $data['token'] ?? null,
            is_array($data) ? $data : []
        );
    }

    public function parseWebhook(Request $request): WebhookEvent
    {
        $payload = $request->all();

        $reference    = (string) ($payload['order_id'] ?? '');
        $statusCode   = (string) ($payload['status_code'] ?? '');
        $grossAmount  = (string) ($payload['gross_amount'] ?? '');
        $signatureKey = (string) ($payload['signature_key'] ?? '');

        $expected = hash('sha512', $reference . $statusCode . $grossAmount . (string) $this->config['server_key']);

        if ($signatureKey === '' || ! hash_equals($expected, $signatureKey)) {
            throw new InvalidWebhookSignature('Signature webhook Midtrans tidak valid.');
        }

        $providerStatus = (string) ($payload['transaction_status'] ?? 'pending');
        $fraudStatus    = (string) ($payload['fraud_status'] ?? 'accept');

        return new WebhookEvent(
            (string) ($payload['transaction_id'] ?? $reference . ':' . $providerStatus),
            $reference,
            $this->mapStatus($providerStatus, $fraudStatus),
            $providerStatus,
            isset($payload['gross_amount']) ? (int) $payload['gross_amount'] : null,
            $payload['payment_type'] ?? null,
            $payload
        );
    }

    /**
     * Batasi kanal pembayaran sesuai metode pilihan user.
     *
     * @return array<int, string>
     */
    protected function enabledPayments(string $method): array
    {
        $map = [
            'QRIS'          => ['qris', 'gopay', 'shopeepay'],
            'DANA'          => ['dana'],
            'GoPay'         => ['gopay'],
            'OVO'           => ['ovo'],
            'Bank Transfer' => ['bank_transfer', 'echannel', 'permata_va', 'bca_va', 'bni_va', 'bri_va'],
        ];

        return $map[$method] ?? ['qris', 'bank_transfer'];
    }

    /**
     * Petakan status Midtrans ke status internal.
     */
    protected function mapStatus(string $status, string $fraud): string
    {
        if ($status === 'capture') {
            return $fraud === 'challenge' ? 'pending' : 'success';
        }

        return [
            'settlement' => 'success',
            'pending'    => 'pending',
            'expire'     => 'cancelled',
            'cancel'     => 'cancelled',
            'deny'       => 'failed',
            'failure'    => 'failed',
            'refund'     => 'failed',
        ][$status] ?? 'pending';
    }
}
