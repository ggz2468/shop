<?php

namespace App\Listeners;

use App\Enums\Order\PaymentMethod;
use App\Enums\PaymentTransaction\Provider;
use App\Enums\PaymentTransaction\Status;
use App\Events\OrderCreated;
use App\Events\PaymentInitiated;
use App\Models\Order;
use App\Repositories\OrderRepository;
use App\Repositories\PaymentTransactionRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CreatePaymentTransaction implements ShouldQueue
{
    /**
     * @param \App\Repositories\OrderRepository $orderRepository
     * @param \App\Repositories\PaymentTransactionRepository $paymentTransactionRepository
     * @param \Illuminate\Contracts\Events\Dispatcher $events
     * @return void
     */
    public function __construct(
        private OrderRepository $orderRepository,
        private PaymentTransactionRepository $paymentTransactionRepository,
        private Dispatcher $events,
    ) {
        
    }

    /**
     * @param \App\Events\OrderCreated $event
     * @return void
     */
    public function handle(OrderCreated $event): void
    {
        $order = $this->orderRepository->first(['id', $event->orderId]);

        if (!$order instanceof Order) {
            throw new ModelNotFoundException("Order with ID {$event->orderId} not found.");
        }

        $existingPaymentTransaction = $this->paymentTransactionRepository->first(['order_id', $order->id]);

        if ($existingPaymentTransaction !== null) {
            return;
        }

        $paymentTransaction = $this->paymentTransactionRepository->create([
            'order_id' => $order->id,
            'provider' => $this->resolveProvider((int) $order->payment_method),
            'merchant_trade_no' => substr('PAY' . $order->number, 0, 64),
            'amount' => $order->total_amount,
            'currency' => 'TWD',
            'status' => Status::PENDING->value,
            'payment_method' => $order->payment_method,
            'request_payload' => null,
            'checkout_payload' => null,
            'response_payload' => null,
        ]);

        $this->events->dispatch(new PaymentInitiated($paymentTransaction->id));
    }

    private function resolveProvider(int $paymentMethod): int
    {
        return match ($paymentMethod) {
            PaymentMethod::LINE_PAY->value => Provider::LINE_PAY->value,
            PaymentMethod::CASH->value => Provider::CASH->value,
            default => Provider::ECPAY->value,
        };
    }
}
