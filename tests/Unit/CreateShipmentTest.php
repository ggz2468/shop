<?php

namespace Tests\Unit;

use App\Enums\Shipment\ShippingMethod;
use App\Enums\Shipment\Status;
use App\Events\PaymentSucceeded;
use App\Events\ShipmentCreated;
use App\Listeners\CreateShipment;
use App\Models\PaymentTransaction;
use App\Models\Shipment;
use App\Repositories\PaymentTransactionRepository;
use App\Repositories\ShipmentRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateShipmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * PaymentSucceeded: 應為付款完成的訂單建立出貨單並 dispatch ShipmentCreated。
     */
    public function test_handle_creates_shipment_and_dispatches_shipment_created_event(): void
    {
        Event::fake([ShipmentCreated::class]);
        $paymentTransaction = PaymentTransaction::factory()->create();

        $this->makeListener()->handle(new PaymentSucceeded($paymentTransaction->id));

        $shipment = Shipment::query()->where('order_id', $paymentTransaction->order_id)->firstOrFail();
        $this->assertSame(Status::PENDING->value, $shipment->status);
        $this->assertSame(ShippingMethod::HOME_DELIVERY->value, $shipment->shipping_method);
        $this->assertNull($shipment->provider);
        $this->assertNull($shipment->tracking_number);
        Event::assertDispatched(
            ShipmentCreated::class,
            fn (ShipmentCreated $event): bool => $event->shipmentId === $shipment->id,
        );
    }

    /**
     * PaymentSucceeded: 訂單已有出貨單時不應重複建立或再次 dispatch ShipmentCreated。
     */
    public function test_handle_does_not_create_duplicate_shipment_when_order_already_has_one(): void
    {
        Event::fake([ShipmentCreated::class]);
        $paymentTransaction = PaymentTransaction::factory()->create();
        Shipment::factory()->create([
            'order_id' => $paymentTransaction->order_id,
        ]);

        $this->makeListener()->handle(new PaymentSucceeded($paymentTransaction->id));

        $this->assertSame(1, Shipment::query()->where('order_id', $paymentTransaction->order_id)->count());
        Event::assertNotDispatched(ShipmentCreated::class);
    }

    /**
     * PaymentSucceeded: 找不到付款交易時應拋出例外，讓 queue job 可重試或進 failed jobs。
     */
    public function test_handle_throws_model_not_found_exception_when_payment_transaction_is_missing(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->makeListener()->handle(new PaymentSucceeded(999999));
    }

    private function makeListener(): CreateShipment
    {
        return new CreateShipment(
            new PaymentTransactionRepository,
            new ShipmentRepository,
            app(Dispatcher::class),
        );
    }
}
