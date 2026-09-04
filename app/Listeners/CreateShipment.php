<?php

namespace App\Listeners;

use App\Enums\Shipment\ShippingMethod;
use App\Enums\Shipment\Status;
use App\Events\PaymentSucceeded;
use App\Events\ShipmentCreated;
use App\Models\PaymentTransaction;
use App\Repositories\PaymentTransactionRepository;
use App\Repositories\ShipmentRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CreateShipment implements ShouldQueue
{
    /**
     * @param \App\Repositories\PaymentTransactionRepository $paymentTransactionRepository
     * @param \App\Repositories\ShipmentRepository $shipmentRepository
     * @param \Illuminate\Contracts\Events\Dispatcher $events
     * @return void
     */
    public function __construct(
        private PaymentTransactionRepository $paymentTransactionRepository,
        private ShipmentRepository $shipmentRepository,
        private Dispatcher $events,
    ) {
        
    }

    /**
     * @param \App\Events\PaymentSucceeded $event
     * @return void
     */
    public function handle(PaymentSucceeded $event): void
    {
        $paymentTransaction = $this->paymentTransactionRepository->first(['id', $event->paymentTransactionId]);

        if (!$paymentTransaction instanceof PaymentTransaction) {
            throw new ModelNotFoundException("Payment transaction with ID {$event->paymentTransactionId} not found.");
        }

        $existingShipment = $this->shipmentRepository->first(['order_id', $paymentTransaction->order_id]);

        if ($existingShipment !== null) {
            return;
        }

        $paymentTransaction->load('order.member');
        $order = $paymentTransaction->order;

        if ($order === null) {
            throw new ModelNotFoundException("Order with ID {$paymentTransaction->order_id} not found.");
        }

        $member = $order->member;
        $recipientName = trim((string) ($member?->last_name ?? '') . (string) ($member?->first_name ?? ''));
        $shipment = $this->shipmentRepository->create([
            'order_id' => $order->id,
            'provider' => null,
            'tracking_number' => null,
            'status' => Status::PENDING->value,
            'shipping_method' => ShippingMethod::HOME_DELIVERY->value,
            'recipient_name' => $recipientName !== '' ? $recipientName : '會員',
            'recipient_phone' => (string) ($member?->phone ?? ''),
            'recipient_address' => $member?->address,
            'store_code' => null,
            'request_payload' => null,
            'response_payload' => null,
        ]);

        $this->events->dispatch(new ShipmentCreated($shipment->id));
    }
}
