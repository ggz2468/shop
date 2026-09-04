<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentCreated
{
    use Dispatchable, SerializesModels;

    /**
     * @param int $shipmentId
     * @return void
     */
    public function __construct(
        public int $shipmentId,
    ) {
        
    }
}
