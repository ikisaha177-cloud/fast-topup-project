<?php

namespace App\Services\Payment;

use App\Services\Payment\Drivers\MidtransGateway;
use App\Services\Payment\Drivers\SimulationGateway;
use InvalidArgumentException;

/**
 * Resolver driver pembayaran berdasarkan config("payment.driver").
 */
class PaymentGatewayManager
{
    /**
     * @return \App\Services\Payment\PaymentGateway
     */
    public function driver(?string $name = null)
    {
        $name = $name ?: config('payment.driver', 'simulation');

        switch ($name) {
            case 'midtrans':
                return new MidtransGateway((array) config('payment.drivers.midtrans', []));

            case 'simulation':
                return new SimulationGateway((string) config('payment.drivers.simulation.secret'));

            default:
                throw new InvalidArgumentException('Driver pembayaran tidak dikenal: ' . $name);
        }
    }
}
