<?php

namespace App\Services\Payment\Exceptions;

use RuntimeException;

/**
 * Dilempar saat signature webhook tidak valid / tidak bisa diverifikasi.
 */
class InvalidWebhookSignature extends RuntimeException
{
}
