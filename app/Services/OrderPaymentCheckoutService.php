<?php

namespace App\Services;

use App\Enums\Order\PaymentStatus;
use App\Enums\Order\Status as OrderStatus;
use App\Enums\PaymentTransaction\Provider;
use App\Enums\PaymentTransaction\Status as PaymentTransactionStatus;
use App\Models\Order;

class OrderPaymentCheckoutService
{
    /**
     * 提供客戶端串接金流時所需的資料
     *
     * @return array<string, mixed>
     */
    public function show(int $memberId, Order $order): array
    {
        if ((int) $order->member_id !== $memberId) {
            return [
                'status' => 404,
                'message' => '找不到付款資料。',
            ];
        }

        if ($this->cannotPay($order)) {
            return [
                'status' => 409,
                'message' => '此訂單目前無法付款。',
            ];
        }

        $paymentTransaction = $order->paymentTransactions()
            ->where('status', PaymentTransactionStatus::PENDING->value)
            ->latest('id')
            ->first();

        if ($paymentTransaction === null) {
            return [
                'status' => 404,
                'message' => '找不到可付款的交易資料。',
            ];
        }

        if ($paymentTransaction->request_payload === null || $paymentTransaction->checkout_payload === null) {
            return [
                'status' => 202,
                'message' => '付款資料產生中，請稍後再試。',
                'data' => [
                    'order_id' => $order->id,
                    'payment_transaction_id' => $paymentTransaction->id,
                    'provider' => $paymentTransaction->provider,
                    'status' => $paymentTransaction->status,
                    'ready' => false,
                ],
            ];
        }

        return [
            'status' => 200,
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->number,
                'payment_transaction_id' => $paymentTransaction->id,
                'provider' => $paymentTransaction->provider,
                'provider_name' => strtolower(Provider::tryFrom($paymentTransaction->provider)?->name ?? ''),
                'status' => $paymentTransaction->status,
                'payment_method' => $paymentTransaction->payment_method,
                'amount' => $paymentTransaction->amount,
                'currency' => $paymentTransaction->currency,
                'checkout_payload' => $paymentTransaction->checkout_payload,
                'request_payload' => $paymentTransaction->request_payload,
            ],
        ];
    }

    private function cannotPay(Order $order): bool
    {
        return (int) $order->status !== OrderStatus::STOCKING->value
            || (int) $order->payment_status !== PaymentStatus::UNPAID->value;
    }
}
