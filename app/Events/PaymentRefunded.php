<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentRefunded
{
    use Dispatchable, SerializesModels;

    /**
     * @param int $paymentTransactionId
     * @param array<string, mixed>|null $providerPayload
     * @return void
     */
    public function __construct(
        public int $paymentTransactionId,
        public ?array $providerPayload = null,
    ) {
        
    }
}
