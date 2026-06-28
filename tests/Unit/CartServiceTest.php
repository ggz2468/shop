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
     * 取得購物車內容：應依會員 id 從 CartStore 取出產品項目。
     */
    public function test_get_cart_items_returns_items_from_cart_store_for_member(): void
    {
        $memberId = 1;
        $cartItems = [
            [
                'product_id' => 1,
                'quantity' => 2,
            ],
            [
                'product_id' => 2,
                'quantity' => 1,
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

    /**
     * 加入產品到購物車：應將會員 id、產品 id 與數量交給 CartStore，並回傳加入結果。
     */
    public function test_add_product_adds_item_to_cart_store_for_member(): void
    {
        $memberId = 1;
        $productId = 10;
        $quantity = 2;
        $cartItem = [
            'product_id' => $productId,
            'quantity' => $quantity,
        ];

        $cartStore = Mockery::mock(CartStore::class);
        $cartStore->shouldReceive('storeItem')
            ->once()
            ->with($memberId, $productId, $quantity)
            ->andReturn($cartItem);

        $service = new CartService($cartStore);

        $result = $service->storeCartItem($memberId, $productId, $quantity);

        $this->assertSame([
            'status' => 201,
            'message' => '產品已加入購物車。',
            'data' => $cartItem,
        ], $result);
    }

    /**
     * 加入產品到購物車：CartStore 儲存失敗時應回傳服務不可用狀態。
     */
    public function test_add_product_returns_503_when_cart_store_fails_to_store_item(): void
    {
        $memberId = 1;
        $productId = 10;
        $quantity = 2;

        $cartStore = Mockery::mock(CartStore::class);
        $cartStore->shouldReceive('storeItem')
            ->once()
            ->with($memberId, $productId, $quantity)
            ->andReturn(false);

        $service = new CartService($cartStore);

        $result = $service->storeCartItem($memberId, $productId, $quantity);

        $this->assertSame([
            'status' => 503,
            'message' => '產品加入會員購物車失敗，請稍後再試。',
        ], $result);
        $this->assertArrayNotHasKey('data', $result);
    }
}
