<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationLinkNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly string $verificationUrl,
        private readonly int $expiresInMinutes = 10,
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
        $memberName = trim(($notifiable->first_name ?? '').' '.($notifiable->last_name ?? ''));

        return (new MailMessage)
            ->subject('電子郵件驗證連結通知信')
            ->view('emails.verification', [
                'memberName' => $memberName !== '' ? $memberName : '會員',
                'verificationUrl' => $this->verificationUrl,
                'expiresInMinutes' => $this->expiresInMinutes,
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
            'verification_url' => $this->verificationUrl,
            'expires_in_minutes' => $this->expiresInMinutes,
        ];
    }
}
