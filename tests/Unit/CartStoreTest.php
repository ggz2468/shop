<?php

namespace Tests\Unit;

use App\Stores\CartStore;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use PHPUnit\Framework\TestCase;

class CartStoreTest extends TestCase
{
    /**
     * 取得購物車商品項目：應依會員 id 從 cache 取出商品項目。
     */
    public function test_get_items_returns_cached_items_for_member(): void
    {
        $memberId = 1;
        $cartItems = [
            [
                'product_id' => 1,
                'quantity' => 2,
                'price' => 100,
                'subtotal' => 200,
            ],
        ];

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('cart:member:1:items', [])
            ->willReturn($cartItems);

        $store = new CartStore($cache);

        $result = $store->getItems($memberId);

        $this->assertSame($cartItems, $result);
    }

    /**
     * 取得購物車商品項目：cache 沒有資料時應回傳空陣列。
     */
    public function test_get_items_returns_empty_array_when_cache_missing(): void
    {
        $memberId = 1;

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('cart:member:1:items', [])
            ->willReturn([]);

        $store = new CartStore($cache);

        $result = $store->getItems($memberId);

        $this->assertSame([], $result);
    }

    /**
     * 取得購物車商品項目：cache 資料不是陣列時應回傳空陣列。
     */
    public function test_get_items_returns_empty_array_when_cached_value_is_not_array(): void
    {
        $memberId = 1;

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('cart:member:1:items', [])
            ->willReturn('invalid-cart-items');

        $store = new CartStore($cache);

        $result = $store->getItems($memberId);

        $this->assertSame([], $result);
    }
}
