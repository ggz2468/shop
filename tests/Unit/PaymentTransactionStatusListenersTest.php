<?php

namespace Tests\Unit;

use App\Enums\Order\PaymentStatus;
use App\Enums\PaymentTransaction\Status;
use App\Events\PaymentAuthorized;
use App\Events\PaymentFailed;
use App\Events\PaymentRefunded;
use App\Events\PaymentSucceeded;
use App\Listeners\MarkOrderAsPaid;
use App\Listeners\MarkOrderPaymentAsFailed;
use App\Listeners\MarkOrderPaymentAsPending;
use App\Listeners\MarkOrderPaymentAsRefunded;
use App\Models\PaymentTransaction;
use App\Repositories\OrderRepository;
use App\Repositories\PaymentTransactionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTransactionStatusListenersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * PaymentSucceeded: provider payload 含 TradeNo 時應儲存金流服務商交易編號。
     */
    public function test_payment_succeeded_stores_provider_transaction_id_from_trade_no(): void
    {
        $paymentTransaction = PaymentTransaction::factory()->create([
            'provider_transaction_id' => null,
        ]);
        $providerPayload = [
            'TradeNo' => '250906150000001',
            'RtnCode' => '1',
        ];

        $this->makeMarkOrderAsPaidListener()->handle(new PaymentSucceeded($paymentTransaction->id, $providerPayload));

        $paymentTransaction->refresh();
        $this->assertSame('250906150000001', $paymentTransaction->provider_transaction_id);
        $this->assertSame(Status::PAID->value, $paymentTransaction->status);
        $this->assertEquals($providerPayload, $paymentTransaction->response_payload);
        $this->assertSame(PaymentStatus::PAID->value, $paymentTransaction->order->refresh()->payment_status);
    }

    /**
     * PaymentFailed: provider payload 含 TradeNo 時應儲存金流服務商交易編號。
     */
    public function test_payment_failed_stores_provider_transaction_id_from_trade_no(): void
    {
        $paymentTransaction = PaymentTransaction::factory()->create([
            'provider_transaction_id' => null,
        ]);
        $providerPayload = [
            'TradeNo' => '250906150000002',
            'RtnCode' => '10100073',
        ];

        $this->makeMarkOrderPaymentAsFailedListener()->handle(new PaymentFailed($paymentTransaction->id, 'Paid failed', $providerPayload));

        $paymentTransaction->refresh();
        $this->assertSame('250906150000002', $paymentTransaction->provider_transaction_id);
        $this->assertSame(Status::FAILED->value, $paymentTransaction->status);
        $this->assertEquals([
            ...$providerPayload,
            'reason' => 'Paid failed',
        ], $paymentTransaction->response_payload);
        $this->assertSame(PaymentStatus::FAILED->value, $paymentTransaction->order->refresh()->payment_status);
    }

    /**
     * PaymentAuthorized: provider payload 含 TradeNo 時應儲存金流服務商交易編號並標記付款處理中。
     */
    public function test_payment_authorized_stores_provider_transaction_id_and_marks_order_payment_pending(): void
    {
        $paymentTransaction = PaymentTransaction::factory()->create([
            'provider_transaction_id' => null,
        ]);
        $providerPayload = [
            'TradeNo' => '250906150000004',
            'RtnCode' => '2',
            'PaymentType' => 'ATM_TAISHIN',
        ];

        $this->makeMarkOrderPaymentAsPendingListener()->handle(new PaymentAuthorized($paymentTransaction->id, $providerPayload));

        $paymentTransaction->refresh();
        $this->assertSame('250906150000004', $paymentTransaction->provider_transaction_id);
        $this->assertSame(Status::AUTHORIZED->value, $paymentTransaction->status);
        $this->assertEquals($providerPayload, $paymentTransaction->response_payload);
        $this->assertSame(PaymentStatus::PENDING->value, $paymentTransaction->order->refresh()->payment_status);
    }

    /**
     * PaymentRefunded: provider payload 含 TradeNo 時應儲存金流服務商交易編號。
     */
    public function test_payment_refunded_stores_provider_transaction_id_from_trade_no(): void
    {
        $paymentTransaction = PaymentTransaction::factory()->create([
            'provider_transaction_id' => null,
        ]);
        $providerPayload = [
            'TradeNo' => '250906150000003',
            'RtnCode' => '1',
        ];

        $this->makeMarkOrderPaymentAsRefundedListener()->handle(new PaymentRefunded($paymentTransaction->id, $providerPayload));

        $paymentTransaction->refresh();
        $this->assertSame('250906150000003', $paymentTransaction->provider_transaction_id);
        $this->assertSame(Status::REFUNDED->value, $paymentTransaction->status);
        $this->assertEquals($providerPayload, $paymentTransaction->response_payload);
        $this->assertSame(PaymentStatus::REFUNDED->value, $paymentTransaction->order->refresh()->payment_status);
    }

    /**
     * PaymentSucceeded: provider payload 未含 TradeNo 時不應覆寫既有金流服務商交易編號。
     */
    public function test_payment_succeeded_does_not_overwrite_provider_transaction_id_when_trade_no_is_missing(): void
    {
        $paymentTransaction = PaymentTransaction::factory()->create([
            'provider_transaction_id' => 'EXISTING_PROVIDER_TRANSACTION_ID',
        ]);

        $this->makeMarkOrderAsPaidListener()->handle(new PaymentSucceeded($paymentTransaction->id, [
            'RtnCode' => '1',
        ]));

        $paymentTransaction->refresh();
        $this->assertSame('EXISTING_PROVIDER_TRANSACTION_ID', $paymentTransaction->provider_transaction_id);
    }

    private function makeMarkOrderAsPaidListener(): MarkOrderAsPaid
    {
        return new MarkOrderAsPaid(
            new PaymentTransactionRepository,
            new OrderRepository,
        );
    }

    private function makeMarkOrderPaymentAsFailedListener(): MarkOrderPaymentAsFailed
    {
        return new MarkOrderPaymentAsFailed(
            new PaymentTransactionRepository,
            new OrderRepository,
        );
    }

    private function makeMarkOrderPaymentAsPendingListener(): MarkOrderPaymentAsPending
    {
        return new MarkOrderPaymentAsPending(
            new PaymentTransactionRepository,
            new OrderRepository,
        );
    }

    private function makeMarkOrderPaymentAsRefundedListener(): MarkOrderPaymentAsRefunded
    {
        return new MarkOrderPaymentAsRefunded(
            new PaymentTransactionRepository,
            new OrderRepository,
        );
    }
}
