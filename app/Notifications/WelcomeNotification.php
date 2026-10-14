<?php

namespace App\Notifications;

use App\Support\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->from(Contact::supportEmail(), 'FastTopup Store')
            ->subject('Selamat datang di FastTopup Store')
            ->greeting('Halo ' . ($notifiable->name ?? 'Pengguna') . '!')
            ->line('Akun kamu berhasil dibuat. Sekarang kamu bisa top up game dan belanja di marketplace.')
            ->action('Mulai Top Up', route('topup.index'))
            ->line('Terima kasih telah bergabung dengan FastTopup Store.');
    }
}

