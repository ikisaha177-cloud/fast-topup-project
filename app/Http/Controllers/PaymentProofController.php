<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrder;
use App\Models\Transaction;
use Illuminate\Support\Facades\Storage;

class PaymentProofController extends Controller
{
    /** Serve private payment proof only to its owner or an admin. */
    public function show(string $type, string $code)
    {
        if ($type === 'topup') {
            $record = Transaction::where('transaction_code', $code)->firstOrFail();
        } elseif ($type === 'marketplace') {
            $record = MarketplaceOrder::where('order_code', $code)->firstOrFail();
        } else {
            abort(404);
        }

        $this->authorize('view', $record);

        abort_unless($record->hasPaymentProof(), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($record->payment_proof), 404);

        return $disk->response($record->payment_proof, null, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
