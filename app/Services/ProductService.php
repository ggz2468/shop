<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Cache\CacheManager;
use Illuminate\Redis\RedisManager;

class ProductService
{
    /**
     * 建構子
     *
     * @return void
     */
    public function __construct(
        private ProductRepository $productRepository,
        private CacheManager $cache,
        private RedisManager $redis,
    ) {}

    /**
     * 取得熱門產品: 熱門產品為依據被瀏覽次數由多至少前十名的產品
     *
     * @param  int  $rowCountsPerPage  每頁資料筆數
     * @param  int  $page  頁碼
     * @return array<string, array<int, array<string, mixed>>|int>
     */
    public function getPopularProducts(int $rowCountsPerPage = ProductRepository::DEFAULT_ROW_COUNTS_PER_PAGE, int $page = ProductRepository::DEFAULT_PAGE)
    {
        return $this->productRepository->getProducts($rowCountsPerPage, $page);
    }

    /**
     * 取得單一產品資料
     *
     * @return array<string, mixed>
     */
    public function getProductData(Product $product)
    {
        $cacheKey = ProductRepository::cacheKey($product->id);
        $productCache = $this->cache->tags(['products']);

        // 將儲存於 Redis 中的產品被瀏覽次數遞增
        $this->redis->zIncrby('product_view_counts', 1, (string) $product->id);

        // 如果產品資料存在於 Cache 中，直接從 Cache 中取得並回傳
        if ($productCache->has($cacheKey)) {
            return $productCache->get($cacheKey);
        }

        $product->load(['images', 'variants.productSpec']);
        $productData = [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'view_counts' => $product->view_counts,
            'image_path' => $product->images->first()?->url ?? '/images/products/default.svg',
            'variants' => $product->variants->map(fn ($variant) => [
                'id' => $variant->id,
                'product_spec_id' => $variant->product_spec_id,
                'sku' => $variant->sku,
                'price' => $variant->price,
                'stock_quantity' => $variant->stock_quantity,
                'spec' => [
                    'color' => $variant->productSpec->color,
                    'size' => $variant->productSpec->size,
                ],
            ])->values()->all(),
        ];

        // 將產品資料存入 Cache 中，並設定過期時間為 1 小時
        $productCache->put($cacheKey, $productData, 3600);

        return $productData;
    }
}
