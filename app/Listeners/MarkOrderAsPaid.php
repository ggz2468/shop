<?php

namespace App\Listeners;

use App\Enums\Order\PaymentStatus;
use App\Enums\PaymentTransaction\Status;
use App\Events\PaymentSucceeded;
use App\Models\PaymentTransaction;
use App\Repositories\OrderRepository;
use App\Repositories\PaymentTransactionRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MarkOrderAsPaid implements ShouldQueue
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
     * @param \App\Events\PaymentSucceeded $event
     * @return void
     */
    public function handle(PaymentSucceeded $event): void
    {
        $paymentTransaction = $this->paymentTransactionRepository->first(['id', $event->paymentTransactionId]);

        if (!$paymentTransaction instanceof PaymentTransaction) {
            throw new ModelNotFoundException("Payment transaction with ID {$event->paymentTransactionId} not found.");
        }

        $paymentTransactionData = [
            'status' => Status::PAID->value,
            'paid_at' => now(),
        ];

        if ($event->providerPayload !== null) {
            $paymentTransactionData['response_payload'] = $event->providerPayload;
        }

        $this->paymentTransactionRepository->update(['id', $paymentTransaction->id], $paymentTransactionData);
        $this->orderRepository->update(['id', $paymentTransaction->order_id], [
            'payment_status' => PaymentStatus::PAID->value,
        ]);
    }
}
