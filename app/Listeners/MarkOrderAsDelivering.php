<?php

namespace App\Listeners;

use App\Enums\Order\Status as OrderStatus;
use App\Enums\Shipment\Status as ShipmentStatus;
use App\Events\ShipmentShipped;
use App\Models\Shipment;
use App\Repositories\OrderRepository;
use App\Repositories\ShipmentRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MarkOrderAsDelivering implements ShouldQueue
{
    /**
     * @param \App\Repositories\ShipmentRepository $shipmentRepository
     * @param \App\Repositories\OrderRepository $orderRepository
     * @return void
     */
    public function __construct(
        private ShipmentRepository $shipmentRepository,
        private OrderRepository $orderRepository,
    ) {
        
    }

    /**
     * @param \App\Events\ShipmentShipped $event
     * @return void
     */
    public function handle(ShipmentShipped $event): void
    {
        $shipment = $this->shipmentRepository->first(['id', $event->shipmentId]);

        if (!$shipment instanceof Shipment) {
            throw new ModelNotFoundException("Shipment with ID {$event->shipmentId} not found.");
        }

        $shipmentData = [
            'status' => ShipmentStatus::SHIPPED->value,
            'shipped_at' => now(),
        ];

        if ($event->providerPayload !== null) {
            $shipmentData['response_payload'] = $event->providerPayload;
        }

        $this->shipmentRepository->update(['id', $shipment->id], $shipmentData);
        $this->orderRepository->update(['id', $shipment->order_id], [
            'status' => OrderStatus::DELIVERING->value,
        ]);
    }
}
