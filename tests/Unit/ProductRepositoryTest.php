<?php

namespace Tests\Unit;

use App\Models\Image;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Cache\CacheManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * 產品列表：產品資料全命中快取時，應直接回傳並依瀏覽次數降冪、id 升冪排序。
     */
    public function test_get_products_returns_sorted_cached_products_when_cache_fully_hits(): void
    {
        $productA = Product::factory()->create([
            'view_counts' => 50,
            'name' => 'A Product',
            'price' => 100,
            'description' => 'A Desc',
        ]);
        $productB = Product::factory()->create([
            'view_counts' => 50,
            'name' => 'B Product',
            'price' => 200,
            'description' => 'B Desc',
        ]);

        $productsIndexCache = Mockery::mock();
        $productsIndexCache->shouldReceive('remember')
            ->once()
            ->with(
                'product_ids:page:1:row_counts_per_page:10',
                3600,
                Mockery::type('callable')
            )
            ->andReturn([$productB->id, $productA->id]);

        $productsCache = Mockery::mock();
        $productsCache->shouldReceive('many')
            ->once()
            ->with(["product:{$productB->id}", "product:{$productA->id}"])
            ->andReturn([
                "product:{$productB->id}" => [
                    'id' => $productB->id,
                    'product_spec_id' => $productB->product_spec_id,
                    'name' => 'B Product',
                    'price' => 200,
                    'description' => 'B Desc',
                    'view_counts' => 50,
                    'image_path' => '/images/products/b.jpg',
                ],
                "product:{$productA->id}" => [
                    'id' => $productA->id,
                    'product_spec_id' => $productA->product_spec_id,
                    'name' => 'A Product',
                    'price' => 100,
                    'description' => 'A Desc',
                    'view_counts' => 50,
                    'image_path' => '/images/products/a.jpg',
                ],
            ]);
        $productsCache->shouldReceive('putMany')
            ->once()
            ->with([], 3600);

        $cacheManager = Mockery::mock(CacheManager::class);
        $cacheManager->shouldReceive('tags')
            ->once()
            ->with(['products_index'])
            ->andReturn($productsIndexCache);
        $cacheManager->shouldReceive('tags')
            ->twice()
            ->with(['products'])
            ->andReturn($productsCache);

        $repository = new ProductRepository($cacheManager);

        $result = $repository->getProducts();

        $this->assertSame(2, $result['total_row_counts']);
        $this->assertCount(2, $result['products']);
        $this->assertSame([$productA->id, $productB->id], array_column($result['products'], 'id'));
    }

    /**
     * 產品列表：部分快取未命中時，應回資料庫查詢並補寫快取，且無圖片時使用預設圖片。
     */
    public function test_get_products_fetches_missing_products_and_uses_default_image_path_when_image_not_exists(): void
    {
        $cachedProduct = Product::factory()->create([
            'view_counts' => 300,
            'name' => 'Cached Product',
            'price' => 1000,
            'description' => 'cached desc',
        ]);
        $dbProductWithImage = Product::factory()->create([
            'view_counts' => 200,
            'name' => 'DB Product With Image',
            'price' => 900,
            'description' => 'db desc with image',
        ]);
        $dbProductWithoutImage = Product::factory()->create([
            'view_counts' => 200,
            'name' => 'DB Product No Image',
            'price' => 800,
            'description' => 'db desc no image',
        ]);

        Image::factory()->create([
            'url' => '/images/products/db-product.jpg',
            'imageable_id' => $dbProductWithImage->id,
            'imageable_type' => Product::class,
        ]);

        $productsIndexCache = Mockery::mock();
        $productsIndexCache->shouldReceive('remember')
            ->once()
            ->with(
                'product_ids:page:1:row_counts_per_page:10',
                3600,
                Mockery::type('callable')
            )
            ->andReturn([
                $cachedProduct->id,
                $dbProductWithImage->id,
                $dbProductWithoutImage->id,
            ]);

        $expectedMissingProducts = [
            "product:{$dbProductWithImage->id}" => [
                'id' => $dbProductWithImage->id,
                'product_spec_id' => $dbProductWithImage->product_spec_id,
                'name' => 'DB Product With Image',
                'price' => 900,
                'description' => 'db desc with image',
                'view_counts' => 200,
                'image_path' => '/images/products/db-product.jpg',
            ],
            "product:{$dbProductWithoutImage->id}" => [
                'id' => $dbProductWithoutImage->id,
                'product_spec_id' => $dbProductWithoutImage->product_spec_id,
                'name' => 'DB Product No Image',
                'price' => 800,
                'description' => 'db desc no image',
                'view_counts' => 200,
                'image_path' => '/images/products/default.svg',
            ],
        ];

        $productsCache = Mockery::mock();
        $productsCache->shouldReceive('many')
            ->once()
            ->with([
                "product:{$cachedProduct->id}",
                "product:{$dbProductWithImage->id}",
                "product:{$dbProductWithoutImage->id}",
            ])
            ->andReturn([
                "product:{$cachedProduct->id}" => [
                    'id' => $cachedProduct->id,
                    'product_spec_id' => $cachedProduct->product_spec_id,
                    'name' => 'Cached Product',
                    'price' => 1000,
                    'description' => 'cached desc',
                    'view_counts' => 300,
                    'image_path' => '/images/products/cached.jpg',
                ],
            ]);
        $productsCache->shouldReceive('putMany')
            ->once()
            ->with($expectedMissingProducts, 3600);

        $cacheManager = Mockery::mock(CacheManager::class);
        $cacheManager->shouldReceive('tags')
            ->once()
            ->with(['products_index'])
            ->andReturn($productsIndexCache);
        $cacheManager->shouldReceive('tags')
            ->twice()
            ->with(['products'])
            ->andReturn($productsCache);

        $repository = new ProductRepository($cacheManager);

        $result = $repository->getProducts();

        $this->assertSame(3, $result['total_row_counts']);
        $this->assertCount(3, $result['products']);
        $this->assertSame(
            [$cachedProduct->id, $dbProductWithImage->id, $dbProductWithoutImage->id],
            array_column($result['products'], 'id')
        );

        $withoutImagePayload = collect($result['products'])
            ->firstWhere('id', $dbProductWithoutImage->id);
        $this->assertSame('/images/products/default.svg', $withoutImagePayload['image_path']);
    }

    /**
     * 產品列表：自訂分頁參數時應使用正確 key，並以 Repository 既定排序條件取產品編號。
     */
    public function test_get_products_uses_custom_pagination_key_and_default_sorting_for_product_ids(): void
    {
        $product1 = Product::factory()->create([
            'view_counts' => 100,
            'name' => 'Product 1',
            'price' => 100,
            'description' => 'desc 1',
        ]);
        $product2 = Product::factory()->create([
            'view_counts' => 90,
            'name' => 'Product 2',
            'price' => 200,
            'description' => 'desc 2',
        ]);
        $product3 = Product::factory()->create([
            'view_counts' => 80,
            'name' => 'Product 3',
            'price' => 300,
            'description' => 'desc 3',
        ]);
        $product4 = Product::factory()->create([
            'view_counts' => 70,
            'name' => 'Product 4',
            'price' => 400,
            'description' => 'desc 4',
        ]);
        $product5 = Product::factory()->create([
            'view_counts' => 60,
            'name' => 'Product 5',
            'price' => 500,
            'description' => 'desc 5',
        ]);

        $productsIndexCache = Mockery::mock();
        $productsIndexCache->shouldReceive('remember')
            ->once()
            ->with(
                'product_ids:page:2:row_counts_per_page:3',
                3600,
                Mockery::type('callable')
            )
            ->andReturnUsing(function (string $key, int $ttl, callable $callback): array {
                return $callback();
            });

        $productsCache = Mockery::mock();
        $productsCache->shouldReceive('many')
            ->once()
            ->with(["product:{$product4->id}", "product:{$product5->id}"])
            ->andReturn([
                "product:{$product5->id}" => [
                    'id' => $product5->id,
                    'product_spec_id' => $product5->product_spec_id,
                    'name' => 'Product 5',
                    'price' => 500,
                    'description' => 'desc 5',
                    'view_counts' => 60,
                    'image_path' => '/images/products/5.jpg',
                ],
                "product:{$product4->id}" => [
                    'id' => $product4->id,
                    'product_spec_id' => $product4->product_spec_id,
                    'name' => 'Product 4',
                    'price' => 400,
                    'description' => 'desc 4',
                    'view_counts' => 70,
                    'image_path' => '/images/products/4.jpg',
                ],
            ]);
        $productsCache->shouldReceive('putMany')
            ->once()
            ->with([], 3600);

        $cacheManager = Mockery::mock(CacheManager::class);
        $cacheManager->shouldReceive('tags')
            ->once()
            ->with(['products_index'])
            ->andReturn($productsIndexCache);
        $cacheManager->shouldReceive('tags')
            ->twice()
            ->with(['products'])
            ->andReturn($productsCache);

        $repository = new ProductRepository($cacheManager);

        $result = $repository->getProducts(3, 2);

        $this->assertSame(5, $result['total_row_counts']);
        $this->assertSame([$product4->id, $product5->id], array_column($result['products'], 'id'));
    }

    /**
     * 產品列表：分頁結果為空時，應回傳空陣列且不觸發資料庫補查。
     */
    public function test_get_products_returns_empty_products_when_cached_page_has_no_ids(): void
    {
        $productsIndexCache = Mockery::mock();
        $productsIndexCache->shouldReceive('remember')
            ->once()
            ->with(
                'product_ids:page:1:row_counts_per_page:10',
                3600,
                Mockery::type('callable')
            )
            ->andReturn([]);

        $productsCache = Mockery::mock();
        $productsCache->shouldReceive('many')
            ->once()
            ->with([])
            ->andReturn([]);
        $productsCache->shouldReceive('putMany')
            ->once()
            ->with([], 3600);

        $cacheManager = Mockery::mock(CacheManager::class);
        $cacheManager->shouldReceive('tags')
            ->once()
            ->with(['products_index'])
            ->andReturn($productsIndexCache);
        $cacheManager->shouldReceive('tags')
            ->twice()
            ->with(['products'])
            ->andReturn($productsCache);

        $repository = new ProductRepository($cacheManager);

        $result = $repository->getProducts();

        $this->assertSame(0, $result['total_row_counts']);
        $this->assertSame([], $result['products']);
    }

    /**
     * 產品列表：索引快取若含不存在的產品 ID，應忽略不存在資料並僅回傳可查得項目。
     */
    public function test_get_products_ignores_stale_ids_from_products_index_cache(): void
    {
        $existingProduct = Product::factory()->create([
            'view_counts' => 20,
            'name' => 'Existing Product',
            'price' => 300,
            'description' => 'existing desc',
        ]);

        $productsIndexCache = Mockery::mock();
        $productsIndexCache->shouldReceive('remember')
            ->once()
            ->with(
                'product_ids:page:1:row_counts_per_page:10',
                3600,
                Mockery::type('callable')
            )
            ->andReturn([$existingProduct->id, 999999]);

        $productsCache = Mockery::mock();
        $productsCache->shouldReceive('many')
            ->once()
            ->with(["product:{$existingProduct->id}", 'product:999999'])
            ->andReturn([]);
        $productsCache->shouldReceive('putMany')
            ->once()
            ->with([
                "product:{$existingProduct->id}" => [
                    'id' => $existingProduct->id,
                    'product_spec_id' => $existingProduct->product_spec_id,
                    'name' => 'Existing Product',
                    'price' => 300,
                    'description' => 'existing desc',
                    'view_counts' => 20,
                    'image_path' => '/images/products/default.svg',
                ],
            ], 3600);

        $cacheManager = Mockery::mock(CacheManager::class);
        $cacheManager->shouldReceive('tags')
            ->once()
            ->with(['products_index'])
            ->andReturn($productsIndexCache);
        $cacheManager->shouldReceive('tags')
            ->twice()
            ->with(['products'])
            ->andReturn($productsCache);

        $repository = new ProductRepository($cacheManager);

        $result = $repository->getProducts();

        $this->assertSame(1, $result['total_row_counts']);
        $this->assertCount(1, $result['products']);
        $this->assertSame([$existingProduct->id], array_column($result['products'], 'id'));
    }

    /**
     * 產品列表：全數快取未命中時，應從資料庫補齊當頁資料並依規則排序後回傳。
     */
    public function test_get_products_fetches_all_products_from_db_when_products_cache_fully_misses(): void
    {
        $popularProduct = Product::factory()->create([
            'view_counts' => 120,
            'name' => 'Popular Product',
            'price' => 900,
            'description' => 'popular desc',
        ]);
        $normalProduct = Product::factory()->create([
            'view_counts' => 50,
            'name' => 'Normal Product',
            'price' => 700,
            'description' => 'normal desc',
        ]);

        $productsIndexCache = Mockery::mock();
        $productsIndexCache->shouldReceive('remember')
            ->once()
            ->with(
                'product_ids:page:1:row_counts_per_page:10',
                3600,
                Mockery::type('callable')
            )
            ->andReturn([$normalProduct->id, $popularProduct->id]);

        $productsCache = Mockery::mock();
        $productsCache->shouldReceive('many')
            ->once()
            ->with(["product:{$normalProduct->id}", "product:{$popularProduct->id}"])
            ->andReturn([]);
        $productsCache->shouldReceive('putMany')
            ->once()
            ->with(Mockery::on(function (array $payload) use ($popularProduct, $normalProduct): bool {
                $expectedKeys = ["product:{$popularProduct->id}", "product:{$normalProduct->id}"];

                foreach ($expectedKeys as $key) {
                    if (!isset($payload[$key])) {
                        return false;
                    }
                }

                return $payload["product:{$popularProduct->id}"]['view_counts'] === 120
                    && $payload["product:{$normalProduct->id}"]['view_counts'] === 50
                    && $payload["product:{$popularProduct->id}"]['image_path'] === '/images/products/default.svg'
                    && $payload["product:{$normalProduct->id}"]['image_path'] === '/images/products/default.svg';
            }), 3600);

        $cacheManager = Mockery::mock(CacheManager::class);
        $cacheManager->shouldReceive('tags')
            ->once()
            ->with(['products_index'])
            ->andReturn($productsIndexCache);
        $cacheManager->shouldReceive('tags')
            ->twice()
            ->with(['products'])
            ->andReturn($productsCache);

        $repository = new ProductRepository($cacheManager);

        $result = $repository->getProducts();

        $this->assertSame(2, $result['total_row_counts']);
        $this->assertSame([$popularProduct->id, $normalProduct->id], array_column($result['products'], 'id'));
    }
}
