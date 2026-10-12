<?php

namespace App\Services\Payment;

/**
 * Hasil pembuatan tagihan (charge) dari gateway.
 */
class PaymentCharge
{
    /** @var string */
    public $reference;

    /** @var int */
    public $amount;

    /** @var string  metode pembayaran yang dipakai */
    public $method;

    /** @var string  status awal menurut gateway, mis. "pending" */
    public $status;

    /** @var string|null  URL pembayaran (Snap redirect / QR) bila ada */
    public $redirectUrl;

    /** @var string|null  payload QR (string QRIS) bila ada */
    public $qrString;

    /** @var string|null  ID transaksi di sisi gateway */
    public $providerReference;

    /** @var array<string, mixed>  data mentah dari gateway */
    public $raw;

    public function __construct(
        string $reference,
        int $amount,
        string $method,
        string $status = 'pending',
        ?string $redirectUrl = null,
        ?string $qrString = null,
        ?string $providerReference = null,
        array $raw = []
    ) {
        $this->reference         = $reference;
        $this->amount            = $amount;
        $this->method            = $method;
        $this->status            = $status;
        $this->redirectUrl       = $redirectUrl;
        $this->qrString          = $qrString;
        $this->providerReference = $providerReference;
        $this->raw               = $raw;
    }

    /**
     * Apakah pembayaran perlu diarahkan ke halaman eksternal?
     */
    public function requiresRedirect(): bool
    {
        return ! empty($this->redirectUrl);
    }
}
