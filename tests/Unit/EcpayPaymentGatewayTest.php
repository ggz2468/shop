<?php

namespace Tests\Unit;

use App\Enums\Order\PaymentMethod;
use App\Gateways\Payments\EcpayPaymentGateway;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentTransaction;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcpayPaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 綠界付款 Gateway: 應依付款交易與訂單明細建立前端表單提交資訊。
     */
    public function test_build_payment_request_returns_ecpay_checkout_form_payload(): void
    {
        $this->setEcpayConfig();

        $order = Order::factory()->create([
            'payment_method' => PaymentMethod::CREDIT_CARD->value,
            'total_amount' => 1280,
        ]);
        $productVariant = ProductVariant::factory()->create([
            'sku' => 'BAG-BLACK-M',
            'price' => 640,
        ]);
        OrderDetail::factory()
            ->for($order)
            ->forProductVariant($productVariant, 2)
            ->create();
        $paymentTransaction = PaymentTransaction::factory()->for($order)->create([
            'merchant_trade_no' => 'PAYORD202609050001',
            'amount' => 1280,
            'payment_method' => PaymentMethod::CREDIT_CARD->value,
            'created_at' => '2026-09-05 10:30:00',
        ]);

        $paymentRequest = app(EcpayPaymentGateway::class)->buildPaymentRequest($paymentTransaction);

        $this->assertSame('https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5', $paymentRequest['action']);
        $this->assertSame('POST', $paymentRequest['method']);
        $this->assertSame('3002599', $paymentRequest['params']['MerchantID']);
        $this->assertSame('PAYORD202609050001', $paymentRequest['params']['MerchantTradeNo']);
        $this->assertSame('2026/09/05 10:30:00', $paymentRequest['params']['MerchantTradeDate']);
        $this->assertSame('aio', $paymentRequest['params']['PaymentType']);
        $this->assertSame(1280, $paymentRequest['params']['TotalAmount']);
        $this->assertSame('Order payment', $paymentRequest['params']['TradeDesc']);
        $this->assertStringContainsString(' x 2', $paymentRequest['params']['ItemName']);
        $this->assertSame('http://localhost/api/payment-callbacks/ecpay', $paymentRequest['params']['ReturnURL']);
        $this->assertSame('http://localhost/orders', $paymentRequest['params']['ClientBackURL']);
        $this->assertSame('Credit', $paymentRequest['params']['ChoosePayment']);
        $this->assertSame(1, $paymentRequest['params']['EncryptType']);
        $this->assertMatchesRegularExpression('/^[A-F0-9]{64}$/', $paymentRequest['params']['CheckMacValue']);
        $this->assertArrayNotHasKey('HashKey', $paymentRequest['params']);
        $this->assertArrayNotHasKey('HashIV', $paymentRequest['params']);
    }

    /**
     * 綠界付款 Gateway: 非信用卡付款方式目前應交由綠界付款頁列出可用方式。
     */
    public function test_build_payment_request_uses_all_choose_payment_for_non_credit_card_payment_method(): void
    {
        $this->setEcpayConfig();

        $order = Order::factory()->create([
            'payment_method' => PaymentMethod::LINE_PAY->value,
        ]);
        $paymentTransaction = PaymentTransaction::factory()->for($order)->create([
            'payment_method' => PaymentMethod::LINE_PAY->value,
        ]);

        $paymentRequest = app(EcpayPaymentGateway::class)->buildPaymentRequest($paymentTransaction);

        $this->assertSame('ALL', $paymentRequest['params']['ChoosePayment']);
    }

    private function setEcpayConfig(): void
    {
        config()->set('services.ecpay.merchant_id', '3002599');
        config()->set('services.ecpay.hash_key', 'spPjZn66i0OhqJsQ');
        config()->set('services.ecpay.hash_iv', 'hT5OJckN45isQTTs');
        config()->set('services.ecpay.payment_action_url', 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5');
        config()->set('services.ecpay.return_url', 'http://localhost/api/payment-callbacks/ecpay');
        config()->set('services.ecpay.client_back_url', 'http://localhost/orders');
    }
}
