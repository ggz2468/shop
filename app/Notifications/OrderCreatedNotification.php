<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCreatedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private Order $order,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $this->order->loadMissing('orderDetails');

        return (new MailMessage)
            ->subject('訂單已建立')
            ->view('emails.order-created', [
                'memberName' => trim(($notifiable->first_name ?? '').' '.($notifiable->last_name ?? '')) ?: '會員',
                'orderNumber' => $this->order->number,
                'totalAmount' => $this->order->total_amount,
                'taxAmount' => $this->order->tax_amount,
                'shippingFee' => $this->order->shipping_fee,
                'itemsSubtotal' => $this->order->orderDetails->sum('subtotal'),
                'orderDetails' => $this->order->orderDetails,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->number,
        ];
    }
}
