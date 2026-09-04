<?php

namespace App\Listeners;

use App\Repositories\OrderRepository;
use App\Notifications\OrderCreatedNotification;
use App\Events\OrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SendOrderCreatedNotification implements ShouldQueue
{
    /**
     * @param \App\Repositories\OrderRepository $orderRepository
     * @return void
     */
    public function __construct(
        private OrderRepository $orderRepository,
    ) {
        
    }

    /**
     * @param \App\Events\OrderCreated $event
     * @return void
     * 
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function handle(OrderCreated $event): void
    {
        $order = $this->orderRepository->first(['id', $event->orderId]);

        if ($order === null) {
            throw new ModelNotFoundException("Order with ID {$event->orderId} not found.");
        }

        $order->member->notify(new OrderCreatedNotification($order));
    }
}
