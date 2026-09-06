<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentAuthorized
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<string, mixed>|null  $providerPayload
     * @return void
     */
    public function __construct(
        public int $paymentTransactionId,
        public ?array $providerPayload = null,
    ) {}
}
