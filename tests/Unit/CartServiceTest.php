<?php

namespace Tests\Unit;

use App\Services\CartService;
use App\Stores\CartStore;
use Mockery;
use PHPUnit\Framework\TestCase;

class CartServiceTest extends TestCase
{
    /**
     * @return void
     */
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * 取得購物車內容：應依會員 id 從 CartStore 取出商品項目。
     */
    public function test_get_cart_items_returns_items_from_cart_store_for_member(): void
    {
        $memberId = 1;
        $cartItems = [
            [
                'product_id' => 1,
                'quantity' => 2,
                'price' => 100,
                'subtotal' => 200,
            ],
            [
                'product_id' => 2,
                'quantity' => 1,
                'price' => 200,
                'subtotal' => 200,
            ],
        ];

        $cartStore = Mockery::mock(CartStore::class);
        $cartStore->shouldReceive('getItems')
            ->once()
            ->with($memberId)
            ->andReturn($cartItems);

        $service = new CartService($cartStore);

        $result = $service->getCartItems($memberId);

        $this->assertSame($cartItems, $result);
    }
}
