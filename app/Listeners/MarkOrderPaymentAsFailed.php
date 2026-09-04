<?php

namespace App\Listeners;

use App\Enums\Order\PaymentStatus;
use App\Enums\PaymentTransaction\Status;
use App\Events\PaymentFailed;
use App\Models\PaymentTransaction;
use App\Repositories\OrderRepository;
use App\Repositories\PaymentTransactionRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MarkOrderPaymentAsFailed implements ShouldQueue
{
    /**
     * @param \App\Repositories\PaymentTransactionRepository $paymentTransactionRepository
     * @param \App\Repositories\OrderRepository $orderRepository
     * @return void
     */
    public function __construct(
        private PaymentTransactionRepository $paymentTransactionRepository,
        private OrderRepository $orderRepository,
    ) {
        
    }

    /**
     * @param \App\Events\PaymentFailed $event
     * @return void
     */
    public function handle(PaymentFailed $event): void
    {
        $paymentTransaction = $this->paymentTransactionRepository->first(['id', $event->paymentTransactionId]);

        if (!$paymentTransaction instanceof PaymentTransaction) {
            throw new ModelNotFoundException("Payment transaction with ID {$event->paymentTransactionId} not found.");
        }

        $responsePayload = $event->providerPayload ?? [];

        if ($event->reason !== null) {
            $responsePayload['reason'] = $event->reason;
        }

        $this->paymentTransactionRepository->update(['id', $paymentTransaction->id], [
            'status' => Status::FAILED->value,
            'response_payload' => $responsePayload === [] ? null : $responsePayload,
            'failed_at' => now(),
        ]);
        $this->orderRepository->update(['id', $paymentTransaction->order_id], [
            'payment_status' => PaymentStatus::FAILED->value,
        ]);
    }
}
