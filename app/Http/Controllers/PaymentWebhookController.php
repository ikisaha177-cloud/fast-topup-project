<?php

namespace App\Http\Controllers;

use App\Services\Payment\Exceptions\InvalidWebhookSignature;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\Payment\PaymentWebhookProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Endpoint webhook pembayaran.
 *
 * Route ini berada di luar grup "web" (tanpa session & CSRF) karena
 * dipanggil server-ke-server oleh payment gateway.
 */
class PaymentWebhookController extends Controller
{
    /** @var \App\Services\Payment\PaymentGatewayManager */
    protected $gateways;

    /** @var \App\Services\Payment\PaymentWebhookProcessor */
    protected $processor;

    public function __construct(PaymentGatewayManager $gateways, PaymentWebhookProcessor $processor)
    {
        $this->gateways  = $gateways;
        $this->processor = $processor;
    }

    /**
     * Terima notifikasi pembayaran dari gateway.
     */
    public function handle(Request $request)
    {
        $gateway = $this->gateways->driver();

        try {
            $event = $gateway->parseWebhook($request);
        } catch (InvalidWebhookSignature $e) {
            Log::warning('Webhook ditolak: signature tidak valid.', [
                'driver' => $gateway->name(),
                'ip'     => $request->ip(),
            ]);

            return response()->json(['message' => 'Invalid signature'], 403);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Bad request'], 400);
        }

        $result = $this->processor->process($event, $gateway->name());

        // Gateway mengharapkan 200 OK; event duplikat tetap dianggap sukses.
        return response()->json([
            'message'   => $result['processed'] ? 'OK' : 'Duplicate ignored',
            'reason'    => $result['reason'],
            'reference' => $result['reference'],
        ], 200);
    }
}
