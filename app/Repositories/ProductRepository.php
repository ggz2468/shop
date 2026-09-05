<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Cache\CacheManager;

class ProductRepository extends Repository
{
    public static function cacheKey(int $productId): string
    {
        return "product:{$productId}";
    }

    /**
     * 預設排序欄位
     *
     * @var string
     */
    public const string DEFAULT_SORT_FIELD = 'view_counts';

    /**
     * 預設排序方向
     *
     * @var string
     */
    public const string DEFAULT_SORT_DIRECTION = 'desc';

    /**
     * 預設每頁資料筆數
     *
     * @var int
     */
    public const int DEFAULT_ROW_COUNTS_PER_PAGE = 10;

    /**
     * 預設頁碼
     *
     * @var int
     */
    public const int DEFAULT_PAGE = 1;

    /**
     * 建構子
     *
     * @return void
     */
    public function __construct(
        private CacheManager $cache,
    ) {
        parent::__construct();
    }

    /**
     * 取得產品
     *
     * @param  int  $rowCountsPerPage  每頁資料筆數
     * @param  int  $page  頁碼
     * @return array<string, array<int, array<string, mixed>>|int>
     */
    public function getProducts(int $rowCountsPerPage = self::DEFAULT_ROW_COUNTS_PER_PAGE, int $page = self::DEFAULT_PAGE)
    {
        // 定義資料總筆數
        $totalRowCounts = Product::count();

        // 取得產品編號
        $productIdsCacheKey = "product_ids:page:{$page}:row_counts_per_page:{$rowCountsPerPage}";
        $productIds = $this->cache->tags(['products_index'])->remember($productIdsCacheKey, 3600, function () use ($rowCountsPerPage, $page) {
            $paginator = $this->paginate([], ['images', 'variants.productSpec'], [[self::DEFAULT_SORT_FIELD, self::DEFAULT_SORT_DIRECTION], ['id', 'asc']], $rowCountsPerPage, $page);

            return collect($paginator->items())
                ->pluck('id')
                ->all();
        });

        // 將產品編號組合成 Cache key
        $cacheKeys = array_map(fn ($id) => self::cacheKey($id), $productIds);

        // 取得存在 Cache 中的產品資料
        $products = $this->cache->tags(['products'])->many($cacheKeys);

        // 取得不存在於 Cache 中的產品編號
        $missingProductIds = array_map(
            fn ($key) => (int) str_replace('product:', '', $key), array_filter($cacheKeys, fn ($key) => ! isset($products[$key]))
        );

        // 取得不存在於 Cache 中的產品資料
        $missingProducts = $this->modelClassName::with(['images', 'variants.productSpec'])
            ->whereIn('id', $missingProductIds)
            ->get()
            ->mapWithKeys(fn ($product) => [
                self::cacheKey($product->id) => [
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
                ],
            ])
            ->all();

        // 將原先不存在於 Cache 中的產品資料存入 Cache
        $this->cache->tags(['products'])->putMany($missingProducts, 3600);

        // 將原先存在於 Cache 中的產品資料與剛剛從資料庫中取得的產品資料合併
        $products = array_merge($products, $missingProducts);

        // 再次將產品資料排序
        $products = collect($products)
            ->sortBy([
                [self::DEFAULT_SORT_FIELD, self::DEFAULT_SORT_DIRECTION],
                ['id', 'asc'],
            ])
            ->values()
            ->all();

        return [
            'products' => $products,
            'total_row_counts' => $totalRowCounts,
        ];
    }
}
