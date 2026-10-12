<?php

namespace App\Services\Payment;

/**
 * Webhook dari gateway yang sudah dinormalisasi.
 *
 * Hanya berisi informasi yang kita butuhkan; detail mentah tetap disimpan
 * di $raw untuk keperluan audit.
 */
class WebhookEvent
{
    /** @var string  ID unik event dari gateway (dasar idempotency) */
    public $eventId;

    /** @var string  referensi internal kita (kode transaksi / pesanan) */
    public $reference;

    /** @var string  status hasil pemetaan: pending|success|cancelled|failed */
    public $status;

    /** @var string  status mentah dari gateway, mis. "capture"/"settlement" */
    public $providerStatus;

    /** @var int|null  nominal yang benar-benar dibayar */
    public $paidAmount;

    /** @var string|null  metode pembayaran menurut gateway */
    public $method;

    /** @var array<string, mixed> */
    public $raw;

    public function __construct(
        string $eventId,
        string $reference,
        string $status,
        string $providerStatus,
        ?int $paidAmount = null,
        ?string $method = null,
        array $raw = []
    ) {
        $this->eventId        = $eventId;
        $this->reference      = $reference;
        $this->status         = $status;
        $this->providerStatus = $providerStatus;
        $this->paidAmount     = $paidAmount;
        $this->method         = $method;
        $this->raw            = $raw;
    }
}
