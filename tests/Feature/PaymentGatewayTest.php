<?php

namespace Tests\Feature;

use App\Services\Payment\Drivers\MidtransGateway;
use App\Services\Payment\Drivers\SimulationGateway;
use App\Services\Payment\Exceptions\InvalidWebhookSignature;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Unit test untuk abstraksi payment gateway (tanpa database).
 */
class PaymentGatewayTest extends TestCase
{
    public function test_manager_resolves_simulation_driver_by_default()
    {
        config(['payment.driver' => 'simulation']);

        $driver = (new PaymentGatewayManager())->driver();

        $this->assertInstanceOf(SimulationGateway::class, $driver);
        $this->assertSame('simulation', $driver->name());
    }

    public function test_manager_resolves_midtrans_driver_when_configured()
    {
        config(['payment.driver' => 'midtrans']);

        $driver = (new PaymentGatewayManager())->driver();

        $this->assertInstanceOf(MidtransGateway::class, $driver);
        $this->assertSame('midtrans', $driver->name());
    }

    public function test_manager_rejects_unknown_driver()
    {
        $this->expectException(\InvalidArgumentException::class);

        (new PaymentGatewayManager())->driver('driver-tidak-ada');
    }

    public function test_simulation_gateway_creates_charge_with_reference_and_qr()
    {
        $driver = new SimulationGateway('rahasia-uji');

        $charge = $driver->createCharge('TXN-ABC123', 50000, 'QRIS');

        $this->assertSame('TXN-ABC123', $charge->reference);
        $this->assertSame(50000, $charge->amount);
        $this->assertSame('pending', $charge->status);
        $this->assertNotEmpty($charge->qrString);
        $this->assertFalse($charge->requiresRedirect());
    }

    public function test_simulation_gateway_verifies_its_signature()
    {
        $driver = new SimulationGateway('rahasia-uji');

        $request = Request::create('/payment/webhook', 'POST', [
            'order_id'           => 'TXN-ABC123',
            'transaction_status' => 'settlement',
        ]);

        $expected = hash_hmac('sha256', 'TXN-ABC123|settlement', 'rahasia-uji');
        $request->headers->set('X-Simulation-Signature', $expected);

        $event = $driver->parseWebhook($request);

        $this->assertSame('TXN-ABC123', $event->reference);
        $this->assertSame('success', $event->status);
        $this->assertSame('settlement', $event->providerStatus);
    }

    public function test_simulation_gateway_rejects_bad_signature()
    {
        $driver = new SimulationGateway('rahasia-uji');

        $request = Request::create('/payment/webhook', 'POST', [
            'order_id'           => 'TXN-ABC123',
            'transaction_status' => 'settlement',
        ]);
        $request->headers->set('X-Simulation-Signature', 'palsu');

        $this->expectException(InvalidWebhookSignature::class);

        $driver->parseWebhook($request);
    }

    public function test_midtrans_gateway_maps_provider_status_to_internal_status()
    {
        $driver = new MidtransGateway(['server_key' => 'SB-Mid-server-UJI']);

        $reference   = 'TXN-MIDTRANS-1';
        $statusCode  = '200';
        $grossAmount = '50000.00';
        $signature   = hash('sha512', $reference . $statusCode . $grossAmount . 'SB-Mid-server-UJI');

        $request = Request::create('/payment/webhook', 'POST', [
            'order_id'           => $reference,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_id'     => 'midtrans-txn-1',
            'transaction_status' => 'settlement',
            'payment_type'       => 'qris',
        ]);

        $event = $driver->parseWebhook($request);

        $this->assertSame($reference, $event->reference);
        $this->assertSame('success', $event->status);
        $this->assertSame('midtrans-txn-1', $event->eventId);
    }

    public function test_midtrans_gateway_rejects_invalid_signature()
    {
        $driver = new MidtransGateway(['server_key' => 'SB-Mid-server-UJI']);

        $request = Request::create('/payment/webhook', 'POST', [
            'order_id'           => 'TXN-MIDTRANS-2',
            'status_code'        => '200',
            'gross_amount'       => '50000.00',
            'signature_key'      => 'tidak-valid',
            'transaction_status' => 'settlement',
        ]);

        $this->expectException(InvalidWebhookSignature::class);

        $driver->parseWebhook($request);
    }

    public function test_midtrans_capture_with_fraud_challenge_stays_pending()
    {
        $driver = new MidtransGateway(['server_key' => 'SB-Mid-server-UJI']);

        $reference   = 'TXN-MIDTRANS-3';
        $statusCode  = '200';
        $grossAmount = '10000.00';
        $signature   = hash('sha512', $reference . $statusCode . $grossAmount . 'SB-Mid-server-UJI');

        $request = Request::create('/payment/webhook', 'POST', [
            'order_id'           => $reference,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'capture',
            'fraud_status'       => 'challenge',
        ]);

        $this->assertSame('pending', $driver->parseWebhook($request)->status);
    }
}
