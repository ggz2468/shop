<?php

namespace Tests\Unit;

use App\Stores\CartStore;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class CartStoreTest extends TestCase
{
    /**
     * 取得購物車產品項目：應依會員 id 從 cache 取出產品項目。
     */
    public function test_get_items_returns_cached_items_for_member(): void
    {
        $memberId = 1;
        $cartItems = [
            [
                'product_id' => 1,
                'quantity' => 2,
            ],
        ];

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('cart:member:1:items', [])
            ->willReturn($cartItems);
        $logger = $this->createMock(LoggerInterface::class);

        $store = new CartStore($cache, $logger);

        $result = $store->getItems($memberId);

        $this->assertSame($cartItems, $result);
    }

    /**
     * 取得購物車產品項目：cache 沒有資料時應回傳空陣列。
     */
    public function test_get_items_returns_empty_array_when_cache_missing(): void
    {
        $memberId = 1;

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('cart:member:1:items', [])
            ->willReturn([]);
        $logger = $this->createMock(LoggerInterface::class);

        $store = new CartStore($cache, $logger);

        $result = $store->getItems($memberId);

        $this->assertSame([], $result);
    }

    /**
     * 取得購物車產品項目：cache 資料不是陣列時應回傳空陣列。
     */
    public function test_get_items_returns_empty_array_when_cached_value_is_not_array(): void
    {
        $memberId = 1;

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('cart:member:1:items', [])
            ->willReturn('invalid-cart-items');
        $logger = $this->createMock(LoggerInterface::class);

        $store = new CartStore($cache, $logger);

        $result = $store->getItems($memberId);

        $this->assertSame([], $result);
    }

    /**
     * 儲存購物車產品項目：應將新產品加入會員既有購物車並寫回 cache。
     */
    public function test_store_item_appends_item_to_member_cart_and_persists_it(): void
    {
        $memberId = 1;
        $productId = 10;
        $quantity = 2;
        $existingItems = [
            [
                'product_id' => 1,
                'quantity' => 1,
            ],
        ];
        $storedItem = [
            'product_id' => $productId,
            'quantity' => $quantity,
        ];
        $expectedItems = [
            ...$existingItems,
            $storedItem,
        ];

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('cart:member:1:items', [])
            ->willReturn($existingItems);
        $cache->expects($this->once())
            ->method('put')
            ->with('cart:member:1:items', $expectedItems)
            ->willReturn(true);
        $logger = $this->createMock(LoggerInterface::class);

        $store = new CartStore($cache, $logger);

        $result = $store->storeItem($memberId, $productId, $quantity);

        $this->assertSame($storedItem, $result);
    }

    /**
     * 儲存購物車產品項目：空購物車時應加入第一個產品並寫回 cache。
     */
    public function test_store_item_appends_item_to_empty_member_cart_and_persists_it(): void
    {
        $memberId = 1;
        $productId = 10;
        $quantity = 2;
        $storedItem = [
            'product_id' => $productId,
            'quantity' => $quantity,
        ];

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('cart:member:1:items', [])
            ->willReturn([]);
        $cache->expects($this->once())
            ->method('put')
            ->with('cart:member:1:items', [$storedItem])
            ->willReturn(true);
        $logger = $this->createMock(LoggerInterface::class);

        $store = new CartStore($cache, $logger);

        $result = $store->storeItem($memberId, $productId, $quantity);

        $this->assertSame($storedItem, $result);
    }

    /**
     * 儲存購物車產品項目：相同產品已存在時應累加數量並寫回 cache。
     */
    public function test_store_item_increments_existing_item_quantity_and_persists_it(): void
    {
        $memberId = 1;
        $productId = 10;
        $quantity = 2;
        $existingItems = [
            [
                'product_id' => $productId,
                'quantity' => 1,
            ],
            [
                'product_id' => 20,
                'quantity' => 4,
            ],
        ];
        $storedItem = [
            'product_id' => $productId,
            'quantity' => $quantity,
        ];
        $expectedItems = [
            [
                'product_id' => $productId,
                'quantity' => 3,
            ],
            [
                'product_id' => 20,
                'quantity' => 4,
            ],
        ];

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('cart:member:1:items', [])
            ->willReturn($existingItems);
        $cache->expects($this->once())
            ->method('put')
            ->with('cart:member:1:items', $expectedItems)
            ->willReturn(true);
        $logger = $this->createMock(LoggerInterface::class);

        $store = new CartStore($cache, $logger);

        $result = $store->storeItem($memberId, $productId, $quantity);

        $this->assertSame($storedItem, $result);
    }

    /**
     * 儲存購物車產品項目：cache 寫入失敗時應回傳 false 並記錄錯誤。
     */
    public function test_store_item_returns_false_and_logs_error_when_cache_put_fails(): void
    {
        $memberId = 1;
        $productId = 10;
        $quantity = 2;
        $storedItem = [
            'product_id' => $productId,
            'quantity' => $quantity,
        ];

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('cart:member:1:items', [])
            ->willReturn([]);
        $cache->expects($this->once())
            ->method('put')
            ->with('cart:member:1:items', [$storedItem])
            ->willReturn(false);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('產品加入會員購物車失敗', $this->callback(function (array $context) use ($memberId, $productId, $quantity): bool {
                return $context['member_id'] === $memberId
                    && $context['product_id'] === $productId
                    && $context['quantity'] === $quantity
                    && $context['exception'] instanceof RuntimeException;
            }));

        $store = new CartStore($cache, $logger);

        $result = $store->storeItem($memberId, $productId, $quantity);

        $this->assertFalse($result);
    }

    /**
     * 儲存購物車產品項目：cache 寫入發生例外時應回傳 false 並記錄錯誤。
     */
    public function test_store_item_returns_false_and_logs_error_when_cache_put_throws_exception(): void
    {
        $memberId = 1;
        $productId = 10;
        $quantity = 2;
        $storedItem = [
            'product_id' => $productId,
            'quantity' => $quantity,
        ];
        $exception = new RuntimeException('cache unavailable');

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('cart:member:1:items', [])
            ->willReturn([]);
        $cache->expects($this->once())
            ->method('put')
            ->with('cart:member:1:items', [$storedItem])
            ->willThrowException($exception);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('產品加入會員購物車失敗', $this->callback(function (array $context) use ($memberId, $productId, $quantity, $exception): bool {
                return $context['member_id'] === $memberId
                    && $context['product_id'] === $productId
                    && $context['quantity'] === $quantity
                    && $context['exception'] === $exception;
            }));

        $store = new CartStore($cache, $logger);

        $result = $store->storeItem($memberId, $productId, $quantity);

        $this->assertFalse($result);
    }

    /**
     * 儲存購物車產品項目：cache 資料不是陣列時應視為空購物車。
     */
    public function test_store_item_treats_non_array_cached_value_as_empty_cart(): void
    {
        $memberId = 1;
        $productId = 10;
        $quantity = 2;
        $storedItem = [
            'product_id' => $productId,
            'quantity' => $quantity,
        ];

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('cart:member:1:items', [])
            ->willReturn('invalid-cart-items');
        $cache->expects($this->once())
            ->method('put')
            ->with('cart:member:1:items', [$storedItem])
            ->willReturn(true);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())
            ->method('error');

        $store = new CartStore($cache, $logger);

        $result = $store->storeItem($memberId, $productId, $quantity);

        $this->assertSame($storedItem, $result);
    }

    /**
     * 更新購物車產品項目：產品存在時應更新指定產品數量並寫回 cache。
     */
    public function test_update_item_updates_existing_item_quantity_and_persists_it(): void
    {
        $memberId = 1;
        $productId = 10;
        $quantity = 5;
        $existingItems = [
            [
                'product_id' => 1,
                'quantity' => 2,
            ],
            [
                'product_id' => $productId,
                'quantity' => 1,
            ],
            [
                'product_id' => 20,
                'quantity' => 4,
            ],
        ];
        $updatedItem = [
            'product_id' => $productId,
            'quantity' => $quantity,
        ];
        $expectedItems = [
            [
                'product_id' => 1,
                'quantity' => 2,
            ],
            $updatedItem,
            [
                'product_id' => 20,
                'quantity' => 4,
            ],
        ];

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('cart:member:1:items', [])
            ->willReturn($existingItems);
        $cache->expects($this->once())
            ->method('put')
            ->with('cart:member:1:items', $expectedItems)
            ->willReturn(true);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())
            ->method('error');

        $store = new CartStore($cache, $logger);

        $result = $store->updateItem($memberId, $productId, $quantity);

        $this->assertSame($updatedItem, $result);
    }

    /**
     * 更新購物車產品項目：產品不存在於購物車時應回傳 null 且不寫回 cache。
     */
    public function test_update_item_returns_null_when_item_does_not_exist_in_member_cart(): void
    {
        $memberId = 1;
        $productId = 10;
        $quantity = 5;
        $existingItems = [
            [
                'product_id' => 1,
                'quantity' => 2,
            ],
        ];

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('cart:member:1:items', [])
            ->willReturn($existingItems);
        $cache->expects($this->never())
            ->method('put');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())
            ->method('error');

        $store = new CartStore($cache, $logger);

        $result = $store->updateItem($memberId, $productId, $quantity);

        $this->assertNull($result);
    }

    /**
     * 更新購物車產品項目：cache 資料不是陣列時應視為空購物車並回傳 null。
     */
    public function test_update_item_returns_null_when_cached_value_is_not_array(): void
    {
        $memberId = 1;
        $productId = 10;
        $quantity = 5;

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('cart:member:1:items', [])
            ->willReturn('invalid-cart-items');
        $cache->expects($this->never())
            ->method('put');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())
            ->method('error');

        $store = new CartStore($cache, $logger);

        $result = $store->updateItem($memberId, $productId, $quantity);

        $this->assertNull($result);
    }

    /**
     * 更新購物車產品項目：cache 寫入失敗時應回傳 false 並記錄錯誤。
     */
    public function test_update_item_returns_false_and_logs_error_when_cache_put_fails(): void
    {
        $memberId = 1;
        $productId = 10;
        $quantity = 5;
        $existingItems = [
            [
                'product_id' => $productId,
                'quantity' => 1,
            ],
        ];
        $updatedItem = [
            'product_id' => $productId,
            'quantity' => $quantity,
        ];

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('cart:member:1:items', [])
            ->willReturn($existingItems);
        $cache->expects($this->once())
            ->method('put')
            ->with('cart:member:1:items', [$updatedItem])
            ->willReturn(false);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('更新會員購物車中指定產品的數量失敗', $this->callback(function (array $context) use ($memberId, $productId, $quantity): bool {
                return $context['member_id'] === $memberId
                    && $context['product_id'] === $productId
                    && $context['quantity'] === $quantity
                    && $context['exception'] instanceof RuntimeException;
            }));

        $store = new CartStore($cache, $logger);

        $result = $store->updateItem($memberId, $productId, $quantity);

        $this->assertFalse($result);
    }

    /**
     * 更新購物車產品項目：cache 寫入發生例外時應回傳 false 並記錄錯誤。
     */
    public function test_update_item_returns_false_and_logs_error_when_cache_put_throws_exception(): void
    {
        $memberId = 1;
        $productId = 10;
        $quantity = 5;
        $existingItems = [
            [
                'product_id' => $productId,
                'quantity' => 1,
            ],
        ];
        $updatedItem = [
            'product_id' => $productId,
            'quantity' => $quantity,
        ];
        $exception = new RuntimeException('cache unavailable');

        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('cart:member:1:items', [])
            ->willReturn($existingItems);
        $cache->expects($this->once())
            ->method('put')
            ->with('cart:member:1:items', [$updatedItem])
            ->willThrowException($exception);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('更新會員購物車中指定產品的數量失敗', $this->callback(function (array $context) use ($memberId, $productId, $quantity, $exception): bool {
                return $context['member_id'] === $memberId
                    && $context['product_id'] === $productId
                    && $context['quantity'] === $quantity
                    && $context['exception'] === $exception;
            }));

        $store = new CartStore($cache, $logger);

        $result = $store->updateItem($memberId, $productId, $quantity);

        $this->assertFalse($result);
    }
}
