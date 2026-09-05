<?php

namespace Tests\Unit;

use App\Events\OrderCreated;
use App\Listeners\SendOrderCreatedNotification;
use App\Models\Order;
use App\Notifications\OrderCreatedNotification;
use App\Repositories\OrderRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendOrderCreatedNotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * OrderCreated: 應通知訂單所屬會員。
     */
    public function test_handle_sends_order_created_notification_to_order_member(): void
    {
        Notification::fake();

        $order = Order::factory()->create();

        $this->makeListener()->handle(new OrderCreated($order->id));

        Notification::assertSentTo(
            $order->member,
            OrderCreatedNotification::class,
            fn (OrderCreatedNotification $notification): bool => $notification->toArray($order->member)['order_id'] === $order->id,
        );
    }

    /**
     * OrderCreated: 找不到訂單時應拋出例外，讓 queue job 可重試或進 failed jobs。
     */
    public function test_handle_throws_model_not_found_exception_when_order_is_missing(): void
    {
        Notification::fake();

        $this->expectException(ModelNotFoundException::class);

        $this->makeListener()->handle(new OrderCreated(999999));
    }

    private function makeListener(): SendOrderCreatedNotification
    {
        return new SendOrderCreatedNotification(new OrderRepository);
    }
}
