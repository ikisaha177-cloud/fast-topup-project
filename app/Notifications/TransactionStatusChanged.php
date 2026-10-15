<?php

namespace App\Notifications;

use App\Models\Transaction;
use App\Support\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransactionStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /** @var \App\Models\Transaction */
    public $transaction;

    /** @var string */
    public $headline;

    public function __construct(Transaction $transaction, $headline)
    {
        $this->transaction = $transaction;
        $this->headline    = $headline;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $transaction = $this->transaction;

        return (new MailMessage())
            ->from(Contact::supportEmail(), 'FastTopup Store')
            ->subject('FastTopup Store - ' . $this->headline . ' (' . $transaction->transaction_code . ')')
            ->greeting('Halo ' . ($notifiable->name ?? 'Pengguna') . '!')
            ->line($this->headline . '.')
            ->line('Kode Transaksi: ' . $transaction->transaction_code)
            ->line('Game: ' . ($transaction->gameTitle->name ?? '-'))
            ->line('Nominal: ' . ($transaction->product->name ?? '-'))
            ->line('Jumlah: Rp ' . number_format((int) $transaction->amount, 0, ',', '.'))
            ->line('Status: ' . strtoupper($transaction->status))
            ->action('Lihat Transaksi', route('transactions.show', $transaction->transaction_code))
            ->line('Terima kasih telah menggunakan FastTopup Store.');
    }
}

