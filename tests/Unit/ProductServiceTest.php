<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\ProductService;
use Illuminate\Cache\CacheManager;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\TestCase;

class ProductServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * 取得熱門產品：應使用預設分頁參數呼叫 Repository。
     */
    public function test_get_popular_products_uses_default_pagination_arguments(): void
    {
        $expected = [
            'products' => [
                ['id' => 1, 'name' => 'A Product'],
            ],
            'total_row_counts' => 1,
        ];

        $productRepository = $this->createMock(ProductRepository::class);
        $cacheManager = Mockery::mock(CacheManager::class);
        $redisManager = Mockery::mock(RedisManager::class);
        $productRepository->expects($this->once())
            ->method('getProducts')
            ->with(
                ProductRepository::DEFAULT_ROW_COUNTS_PER_PAGE,
                ProductRepository::DEFAULT_PAGE
            )
            ->willReturn($expected);

        $service = new ProductService($productRepository, $cacheManager, $redisManager);

        $result = $service->getPopularProducts();

        $this->assertSame($expected, $result);
    }

    /**
     * 取得熱門產品：應使用指定分頁參數呼叫 Repository。
     */
    public function test_get_popular_products_uses_custom_pagination_arguments(): void
    {
        $expected = [
            'products' => [
                ['id' => 2, 'name' => 'B Product'],
            ],
            'total_row_counts' => 20,
        ];

        $productRepository = $this->createMock(ProductRepository::class);
        $cacheManager = Mockery::mock(CacheManager::class);
        $redisManager = Mockery::mock(RedisManager::class);
        $productRepository->expects($this->once())
            ->method('getProducts')
            ->with(15, 3)
            ->willReturn($expected);

        $service = new ProductService($productRepository, $cacheManager, $redisManager);

        $result = $service->getPopularProducts(15, 3);

        $this->assertSame($expected, $result);
    }

    /**
     * 取得單一產品：快取命中時應直接回傳快取資料，且不重建產品資料。
     */
    public function test_get_product_data_returns_cached_data_when_cache_hit(): void
    {
        $product = new class extends Product {
            public bool $loadCalled = false;

            public function load($relations)
            {
                $this->loadCalled = true;

                return $this;
            }
        };
        $product->id = 9;

        $cachedData = [
            'id' => 9,
            'name' => 'Cached Product',
            'description' => 'from cache',
            'view_counts' => 777,
            'image_path' => '/images/products/cached.jpg',
            'variants' => [],
        ];

        $redisManager = Mockery::mock(RedisManager::class);
        $redisManager->shouldReceive('zIncrby')
            ->once()
            ->with('product_view_counts', 1, '9');

        $taggedCache = Mockery::mock();
        $taggedCache->shouldReceive('has')
            ->once()
            ->with('product:9')
            ->andReturn(true);
        $taggedCache->shouldReceive('get')
            ->once()
            ->with('product:9')
            ->andReturn($cachedData);
        $taggedCache->shouldNotReceive('put');

        $cacheManager = Mockery::mock(CacheManager::class);
        $cacheManager->shouldReceive('tags')
            ->once()
            ->with(['products'])
            ->andReturn($taggedCache);

        $productRepository = $this->createMock(ProductRepository::class);
        $service = new ProductService($productRepository, $cacheManager, $redisManager);

        $result = $service->getProductData($product);

        $this->assertSame($cachedData, $result);
        $this->assertFalse($product->loadCalled);
    }

    /**
     * 取得單一產品：快取未命中時應載入圖片、組資料並寫入快取。
     */
    public function test_get_product_data_builds_and_caches_data_when_cache_miss_with_image(): void
    {
        $product = new class extends Product {
            public bool $loadCalled = false;

            public function load($relations)
            {
                $this->loadCalled = true;

                return $this;
            }
        };
        $product->id = 11;
        $product->name = 'Fresh Product';
        $product->description = 'fresh from db';
        $product->view_counts = 88;
        $product->setRelation('images', new Collection([(object) ['url' => '/images/products/fresh.jpg']]));
        $product->setRelation('variants', new Collection([(object) [
            'id' => 17,
            'product_spec_id' => 7,
            'sku' => 'FRESH-BLUE-M',
            'price' => 1234,
            'stock_quantity' => 8,
            'productSpec' => (object) ['color' => '藍', 'size' => 3],
        ]]));

        $expectedData = [
            'id' => 11,
            'name' => 'Fresh Product',
            'description' => 'fresh from db',
            'view_counts' => 88,
            'image_path' => '/images/products/fresh.jpg',
            'variants' => [[
                'id' => 17,
                'product_spec_id' => 7,
                'sku' => 'FRESH-BLUE-M',
                'price' => 1234,
                'stock_quantity' => 8,
                'spec' => ['color' => '藍', 'size' => 3],
            ]],
        ];

        $redisManager = Mockery::mock(RedisManager::class);
        $redisManager->shouldReceive('zIncrby')
            ->once()
            ->with('product_view_counts', 1, '11');

        $taggedCache = Mockery::mock();
        $taggedCache->shouldReceive('has')
            ->once()
            ->with('product:11')
            ->andReturn(false);
        $taggedCache->shouldReceive('put')
            ->once()
            ->with('product:11', $expectedData, 3600);
        $taggedCache->shouldNotReceive('get');

        $cacheManager = Mockery::mock(CacheManager::class);
        $cacheManager->shouldReceive('tags')
            ->once()
            ->with(['products'])
            ->andReturn($taggedCache);

        $productRepository = $this->createMock(ProductRepository::class);
        $service = new ProductService($productRepository, $cacheManager, $redisManager);

        $result = $service->getProductData($product);

        $this->assertSame($expectedData, $result);
        $this->assertTrue($product->loadCalled);
    }

    /**
     * 取得單一產品：無任何圖片時應使用預設圖片路徑。
     */
    public function test_get_product_data_uses_default_image_when_images_missing(): void
    {
        $product = new class extends Product {
            public bool $loadCalled = false;

            public function load($relations)
            {
                $this->loadCalled = true;

                return $this;
            }
        };
        $product->id = 21;
        $product->name = 'No Image Product';
        $product->description = 'no images';
        $product->view_counts = 5;
        $product->setRelation('images', new Collection());
        $product->setRelation('variants', new Collection([(object) [
            'id' => 29,
            'product_spec_id' => 9,
            'sku' => 'NO-IMAGE-WHITE-S',
            'price' => 567,
            'stock_quantity' => 3,
            'productSpec' => (object) ['color' => '白', 'size' => 1],
        ]]));

        $expectedData = [
            'id' => 21,
            'name' => 'No Image Product',
            'description' => 'no images',
            'view_counts' => 5,
            'image_path' => '/images/products/default.svg',
            'variants' => [[
                'id' => 29,
                'product_spec_id' => 9,
                'sku' => 'NO-IMAGE-WHITE-S',
                'price' => 567,
                'stock_quantity' => 3,
                'spec' => ['color' => '白', 'size' => 1],
            ]],
        ];

        $redisManager = Mockery::mock(RedisManager::class);
        $redisManager->shouldReceive('zIncrby')
            ->once()
            ->with('product_view_counts', 1, '21');

        $taggedCache = Mockery::mock();
        $taggedCache->shouldReceive('has')
            ->once()
            ->with('product:21')
            ->andReturn(false);
        $taggedCache->shouldReceive('put')
            ->once()
            ->with('product:21', $expectedData, 3600);

        $cacheManager = Mockery::mock(CacheManager::class);
        $cacheManager->shouldReceive('tags')
            ->once()
            ->with(['products'])
            ->andReturn($taggedCache);

        $productRepository = $this->createMock(ProductRepository::class);
        $service = new ProductService($productRepository, $cacheManager, $redisManager);

        $result = $service->getProductData($product);

        $this->assertSame($expectedData, $result);
        $this->assertTrue($product->loadCalled);
    }
}
