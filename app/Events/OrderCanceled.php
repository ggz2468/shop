<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCanceled
{
    use Dispatchable, SerializesModels;

    /**
     * @param int $orderId
     * @return void
     */
    public function __construct(
        public int $orderId,
    ) {
        
    }
}
