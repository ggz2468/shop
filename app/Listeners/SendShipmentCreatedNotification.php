<?php

namespace App\Listeners;

use App\Events\ShipmentCreated;
use App\Models\Shipment;
use App\Repositories\ShipmentRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Psr\Log\LoggerInterface;

class SendShipmentCreatedNotification implements ShouldQueue
{
    /**
     * @return void
     */
    public function __construct(
        private ShipmentRepository $shipmentRepository,
        private LoggerInterface $logger,
    ) {}

    public function handle(ShipmentCreated $event): void
    {
        $shipment = $this->shipmentRepository->first(['id', $event->shipmentId]);

        if (! $shipment instanceof Shipment) {
            throw new ModelNotFoundException("Shipment with ID {$event->shipmentId} not found.");
        }

        $this->logger->info('Shipment created notification is ready.', [
            'shipment_id' => $shipment->id,
            'order_id' => $shipment->order_id,
        ]);
    }
}
