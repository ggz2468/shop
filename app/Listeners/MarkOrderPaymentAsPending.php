<?php

namespace App\Listeners;

use App\Enums\Order\PaymentStatus;
use App\Enums\PaymentTransaction\Status;
use App\Events\PaymentAuthorized;
use App\Models\PaymentTransaction;
use App\Repositories\OrderRepository;
use App\Repositories\PaymentTransactionRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MarkOrderPaymentAsPending
{
    /**
     * @return void
     */
    public function __construct(
        private PaymentTransactionRepository $paymentTransactionRepository,
        private OrderRepository $orderRepository,
    ) {}

    public function handle(PaymentAuthorized $event): void
    {
        $paymentTransaction = $this->paymentTransactionRepository->first(['id', $event->paymentTransactionId]);

        if (! $paymentTransaction instanceof PaymentTransaction) {
            throw new ModelNotFoundException("Payment transaction with ID {$event->paymentTransactionId} not found.");
        }

        $paymentTransactionData = [
            'status' => Status::AUTHORIZED->value,
        ];

        if ($event->providerPayload !== null) {
            $paymentTransactionData['response_payload'] = $event->providerPayload;

            if (! empty($event->providerPayload['TradeNo'])) {
                $paymentTransactionData['provider_transaction_id'] = (string) $event->providerPayload['TradeNo'];
            }
        }

        $this->paymentTransactionRepository->update(['id', $paymentTransaction->id], $paymentTransactionData);
        $this->orderRepository->update(['id', $paymentTransaction->order_id], [
            'payment_status' => PaymentStatus::PENDING->value,
        ]);
    }
}
