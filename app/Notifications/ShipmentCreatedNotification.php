<?php

namespace App\Notifications;

use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShipmentCreatedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private Shipment $shipment,
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
        $this->shipment->loadMissing('order');

        return (new MailMessage)
            ->subject('出貨資訊已建立')
            ->view('emails.shipment-created', [
                'memberName' => trim(($notifiable->first_name ?? '').' '.($notifiable->last_name ?? '')) ?: '會員',
                'orderNumber' => $this->shipment->order->number,
                'trackingNumber' => $this->shipment->tracking_number,
                'recipientName' => $this->shipment->recipient_name,
                'recipientPhone' => $this->shipment->recipient_phone,
                'recipientAddress' => $this->shipment->recipient_address,
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
            'shipment_id' => $this->shipment->id,
            'order_id' => $this->shipment->order_id,
            'tracking_number' => $this->shipment->tracking_number,
        ];
    }
}
