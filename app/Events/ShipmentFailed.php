<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentFailed
{
    use Dispatchable, SerializesModels;

    /**
     * @param int $shipmentId
     * @param string|null $reason
     * @param array<string, mixed>|null $providerPayload
     * @return void
     */
    public function __construct(
        public int $shipmentId,
        public ?string $reason = null,
        public ?array $providerPayload = null,
    ) {
        
    }
}
