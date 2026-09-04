<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentShipped
{
    use Dispatchable, SerializesModels;

    /**
     * @param int $shipmentId
     * @param array<string, mixed>|null $providerPayload
     * @return void
     */
    public function __construct(
        public int $shipmentId,
        public ?array $providerPayload = null,
    ) {
        
    }
}
