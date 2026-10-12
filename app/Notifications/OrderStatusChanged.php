<?php

namespace App\Notifications;

use App\Models\MarketplaceOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChanged extends Notification
{
    use Queueable;

    /** @var \App\Models\MarketplaceOrder */
    public $order;

    /** @var string */
    public $headline;

    public function __construct(MarketplaceOrder $order, $headline)
    {
        $this->order    = $order;
        $this->headline = $headline;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $order = $this->order;
        $items = $order->items->map(function ($item) {
            return '- ' . ($item->product->name ?? 'Produk') . ' x' . $item->quantity;
        })->implode("\n");

        return (new MailMessage())
            ->subject('TopUpMarket - ' . $this->headline . ' (' . $order->order_code . ')')
            ->greeting('Halo ' . ($notifiable->name ?? 'Pengguna') . '!')
            ->line($this->headline . '.')
            ->line('Kode Pesanan: ' . $order->order_code)
            ->line('Total: Rp ' . number_format((int) $order->total_amount, 0, ',', '.'))
            ->line('Status: ' . strtoupper($order->status))
            ->line('Item pesanan:')
            ->line($items ?: '-')
            ->action('Lihat Pesanan', route('orders.show', $order->order_code))
            ->line('Terima kasih telah berbelanja di TopUpMarket.');
    }
}
