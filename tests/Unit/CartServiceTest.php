<?php

namespace Tests\Unit;

use App\Models\Image;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Repositories\ProductVariantRepository;
use App\Services\CartService;
use App\Stores\CartStore;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class CartServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * 取得購物車內容：應依會員 id 從 CartStore 取出產品項目，並補上產品名稱與圖片。
     */
    public function test_get_cart_items_returns_items_with_product_name_and_image_path(): void
    {
        $memberId = 1;
        $cartItems = [
            [
                'product_variant_id' => 1,
                'quantity' => 2,
                'product_name' => '經典白襯衫',
                'product_image_path' => '/images/products/shirt.svg',
            ],
            [
                'product_variant_id' => 2,
                'quantity' => 1,
                'product_name' => '直筒牛仔褲',
                'product_image_path' => '/images/products/jeans.svg',
            ],
        ];

        $cartStore = Mockery::mock(CartStore::class);
        $cartStore->shouldReceive('getItems')
            ->once()
            ->with($memberId)
            ->andReturn($cartItems);

        $productVariantRepository = Mockery::mock(ProductVariantRepository::class);
        $productVariantRepository->shouldReceive('findManyWithProductImages')
            ->once()
            ->with([1, 2])
            ->andReturn(new Collection([
                1 => $this->makeProductVariant(1, '經典白襯衫', '/images/products/shirt.svg'),
                2 => $this->makeProductVariant(2, '直筒牛仔褲', '/images/products/jeans.svg'),
            ]));

        $service = $this->makeService($cartStore, $productVariantRepository);

        $result = $service->getCartItems($memberId);

        $this->assertSame([
            [
                'product_variant_id' => 1,
                'quantity' => 2,
                'product_name' => '經典白襯衫',
                'product_image_path' => '/images/products/shirt.svg',
            ],
            [
                'product_variant_id' => 2,
                'quantity' => 1,
                'product_name' => '直筒牛仔褲',
                'product_image_path' => '/images/products/jeans.svg',
            ],
        ], $result);
    }

    /**
     * 取得購物車內容：購物車為空時不應查詢產品規格資料。
     */
    public function test_get_cart_items_does_not_query_product_variants_when_cart_is_empty(): void
    {
        $memberId = 1;

        $cartStore = Mockery::mock(CartStore::class);
        $cartStore->shouldReceive('getItems')
            ->once()
            ->with($memberId)
            ->andReturn([]);

        $productVariantRepository = Mockery::mock(ProductVariantRepository::class);
        $productVariantRepository->shouldReceive('findManyWithProductImages')->never();

        $service = $this->makeService($cartStore, $productVariantRepository);

        $result = $service->getCartItems($memberId);

        $this->assertSame([], $result);
    }

    /**
     * 取得購物車內容：產品沒有圖片時應使用預設圖片路徑。
     */
    public function test_get_cart_items_uses_default_image_path_when_product_has_no_image(): void
    {
        $memberId = 1;
        $cartItems = [
            [
                'product_variant_id' => 1,
                'quantity' => 2,
                'product_name' => '經典白襯衫',
                'product_image_path' => '/images/products/default.svg',
            ],
        ];

        $cartStore = Mockery::mock(CartStore::class);
        $cartStore->shouldReceive('getItems')
            ->once()
            ->with($memberId)
            ->andReturn($cartItems);

        $productVariantRepository = Mockery::mock(ProductVariantRepository::class);
        $productVariantRepository->shouldReceive('findManyWithProductImages')
            ->once()
            ->with([1])
            ->andReturn(new Collection([
                1 => $this->makeProductVariant(1, '經典白襯衫'),
            ]));

        $service = $this->makeService($cartStore, $productVariantRepository);

        $result = $service->getCartItems($memberId);

        $this->assertSame([
            [
                'product_variant_id' => 1,
                'quantity' => 2,
                'product_name' => '經典白襯衫',
                'product_image_path' => '/images/products/default.svg',
            ],
        ], $result);
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
            'product_variant_id' => $productId,
            'quantity' => $quantity,
            'product_name' => '經典白襯衫',
            'product_image_path' => '/images/products/shirt.svg',
        ];

        $cartStore = Mockery::mock(CartStore::class);
        $cartStore->shouldReceive('storeItem')
            ->once()
            ->with($memberId, $productId, $quantity)
            ->andReturn($cartItem);

        $service = $this->makeService($cartStore);

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

        $service = $this->makeService($cartStore);

        $result = $service->storeCartItem($memberId, $productId, $quantity);

        $this->assertSame([
            'status' => 503,
            'message' => '產品加入會員購物車失敗，請稍後再試。',
        ], $result);
        $this->assertArrayNotHasKey('data', $result);
    }

    /**
     * 更新購物車產品數量：應將會員 id、產品 id 與數量交給 CartStore，並回傳更新結果。
     */
    public function test_update_cart_item_updates_item_quantity_in_cart_store_for_member(): void
    {
        $memberId = 1;
        $productId = 10;
        $quantity = 3;
        $cartItem = [
            'product_variant_id' => $productId,
            'quantity' => $quantity,
            'product_name' => '經典白襯衫',
            'product_image_path' => '/images/products/shirt.svg',
        ];

        $cartStore = Mockery::mock(CartStore::class);
        $cartStore->shouldReceive('updateItem')
            ->once()
            ->with($memberId, $productId, $quantity)
            ->andReturn($cartItem);

        $service = $this->makeService($cartStore);

        $result = $service->updateCartItem($memberId, $productId, $quantity);

        $this->assertSame([
            'status' => 200,
            'message' => '購物車產品數量已更新。',
            'data' => $cartItem,
        ], $result);
    }

    /**
     * 更新購物車產品數量：CartStore 找不到指定產品時應回傳找不到狀態。
     */
    public function test_update_cart_item_returns_404_when_cart_store_cannot_find_item(): void
    {
        $memberId = 1;
        $productId = 10;
        $quantity = 3;

        $cartStore = Mockery::mock(CartStore::class);
        $cartStore->shouldReceive('updateItem')
            ->once()
            ->with($memberId, $productId, $quantity)
            ->andReturn(null);

        $service = $this->makeService($cartStore);

        $result = $service->updateCartItem($memberId, $productId, $quantity);

        $this->assertSame([
            'status' => 404,
            'message' => '會員購物車中找不到指定產品。',
        ], $result);
        $this->assertArrayNotHasKey('data', $result);
    }

    /**
     * 更新購物車產品數量：CartStore 更新失敗時應回傳服務不可用狀態。
     */
    public function test_update_cart_item_returns_503_when_cart_store_fails_to_update_item(): void
    {
        $memberId = 1;
        $productId = 10;
        $quantity = 3;

        $cartStore = Mockery::mock(CartStore::class);
        $cartStore->shouldReceive('updateItem')
            ->once()
            ->with($memberId, $productId, $quantity)
            ->andReturn(false);

        $service = $this->makeService($cartStore);

        $result = $service->updateCartItem($memberId, $productId, $quantity);

        $this->assertSame([
            'status' => 503,
            'message' => '會員購物車產品數量更新失敗，請稍後再試。',
        ], $result);
        $this->assertArrayNotHasKey('data', $result);
    }

    /**
     * 刪除購物車產品：應將會員 id 與產品 id 交給 CartStore，並回傳刪除結果。
     */
    public function test_destroy_cart_item_removes_item_from_cart_store_for_member(): void
    {
        $memberId = 1;
        $productId = 10;
        $cartItem = [
            'product_variant_id' => $productId,
        ];

        $cartStore = Mockery::mock(CartStore::class);
        $cartStore->shouldReceive('destroyItem')
            ->once()
            ->with($memberId, $productId)
            ->andReturn($cartItem);

        $service = $this->makeService($cartStore);

        $result = $service->destroyCartItem($memberId, $productId);

        $this->assertSame([
            'status' => 200,
            'message' => '購物車產品已刪除。',
            'data' => $cartItem,
        ], $result);
    }

    /**
     * 刪除購物車產品：CartStore 找不到指定產品時應回傳找不到狀態。
     */
    public function test_destroy_cart_item_returns_404_when_cart_store_cannot_find_item(): void
    {
        $memberId = 1;
        $productId = 10;

        $cartStore = Mockery::mock(CartStore::class);
        $cartStore->shouldReceive('destroyItem')
            ->once()
            ->with($memberId, $productId)
            ->andReturn(null);

        $service = $this->makeService($cartStore);

        $result = $service->destroyCartItem($memberId, $productId);

        $this->assertSame([
            'status' => 404,
            'message' => '會員購物車中找不到指定產品。',
        ], $result);
        $this->assertArrayNotHasKey('data', $result);
    }

    /**
     * 刪除購物車產品：CartStore 刪除失敗時應回傳服務不可用狀態。
     */
    public function test_destroy_cart_item_returns_503_when_cart_store_fails_to_destroy_item(): void
    {
        $memberId = 1;
        $productId = 10;

        $cartStore = Mockery::mock(CartStore::class);
        $cartStore->shouldReceive('destroyItem')
            ->once()
            ->with($memberId, $productId)
            ->andReturn(false);

        $service = $this->makeService($cartStore);

        $result = $service->destroyCartItem($memberId, $productId);

        $this->assertSame([
            'status' => 503,
            'message' => '會員購物車產品刪除失敗，請稍後再試。',
        ], $result);
        $this->assertArrayNotHasKey('data', $result);
    }

    /**
     * 清空購物車：應將會員 id 交給 CartStore，並回傳成功狀態。
     */
    public function test_clear_cart_clears_member_cart_in_cart_store(): void
    {
        $memberId = 1;

        $cartStore = Mockery::mock(CartStore::class);
        $cartStore->shouldReceive('clearCart')
            ->once()
            ->with($memberId)
            ->andReturnNull();

        $service = $this->makeService($cartStore);

        $result = $service->clearCart($memberId);

        $this->assertSame([
            'status' => 200,
            'message' => '購物車已清空。',
        ], $result);
        $this->assertArrayNotHasKey('data', $result);
    }

    /**
     * 清空購物車：CartStore 清空失敗時應回傳服務不可用狀態。
     */
    public function test_clear_cart_returns_503_when_cart_store_fails_to_clear_cart(): void
    {
        $memberId = 1;

        $cartStore = Mockery::mock(CartStore::class);
        $cartStore->shouldReceive('clearCart')
            ->once()
            ->with($memberId)
            ->andThrow(new RuntimeException('清空會員購物車失敗'));

        $service = $this->makeService($cartStore);

        $result = $service->clearCart($memberId);

        $this->assertSame([
            'status' => 503,
            'message' => '會員購物車清空失敗，請稍後再試。',
        ], $result);
        $this->assertArrayNotHasKey('data', $result);
    }

    private function makeService(CartStore $cartStore, ?ProductVariantRepository $productVariantRepository = null): CartService
    {
        return new CartService(
            $cartStore,
            $productVariantRepository ?? Mockery::mock(ProductVariantRepository::class),
        );
    }

    private function makeProductVariant(int $id, string $productName, ?string $imagePath = null): ProductVariant
    {
        $product = new Product;
        $product->name = $productName;

        $images = new Collection;

        if ($imagePath !== null) {
            $image = new Image;
            $image->url = $imagePath;
            $images->push($image);
        }

        $product->setRelation('images', $images);

        $productVariant = new ProductVariant;
        $productVariant->id = $id;
        $productVariant->setRelation('product', $product);

        return $productVariant;
    }
}
