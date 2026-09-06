<?php

namespace Tests\Unit;

use App\Gateways\Payments\EcpayPaymentGateway;
use App\Repositories\PaymentTransactionRepository;
use App\Services\EcpayPaymentCallbackService;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Tests\TestCase;

class EcpayPaymentCallbackServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * 綠界付款回呼：內部錯誤不應回傳例外訊息給綠界。
     */
    public function test_handle_returns_generic_content_for_internal_error(): void
    {
        $payload = $this->validPayload();

        $ecpayPaymentGateway = Mockery::mock(EcpayPaymentGateway::class);
        $ecpayPaymentGateway->shouldReceive('makeCheckMacValue')
            ->once()
            ->with($payload)
            ->andThrow(new RuntimeException('database password leaked in stack trace'));

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();
        $logger->shouldReceive('warning')->never();

        $service = new EcpayPaymentCallbackService(
            $ecpayPaymentGateway,
            new PaymentTransactionRepository,
            $logger,
            app(ConfigRepository::class),
            app(Dispatcher::class),
        );

        $result = $service->handle($payload);

        $this->assertSame([
            'status' => 500,
            'content' => '0|Internal Server Error',
        ], $result);
    }

    /**
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'MerchantID' => '3002599',
            'MerchantTradeNo' => 'PAY202609060001',
            'StoreID' => '',
            'RtnCode' => '1',
            'RtnMsg' => 'Succeeded',
            'TradeNo' => '250906150000001',
            'TradeAmt' => '1000',
            'PaymentDate' => '2026/09/06 15:00:00',
            'PaymentType' => 'Credit_CreditCard',
            'PaymentTypeChargeFee' => '20',
            'TradeDate' => '2026/09/06 14:58:00',
            'SimulatePaid' => '0',
            'CheckMacValue' => str_repeat('A', 64),
        ];
    }
}
