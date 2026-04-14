<?php

namespace App\Services;

use App\Repositories\ProductRepository;

class ProductService
{
    /**
     * 預設排序欄位
     * 
     * @var string
     */
    public const string DEFAULT_SORT_FIELD = 'view_count';

    /**
     * 預設取得資料筆數
     * 
     * @var int
     */
    public const int DEFAULT_ROW_COUNT = 10;

    /**
     * 建構子
     * 
     * @param \App\Repositories\ProductRepository $productRepository
     * @return void
     */
    public function __construct(
        private ProductRepository $productRepository
    ) {
        
    }

    /**
     * 取得熱門產品。熱門產品為依據被瀏覽次數由多至少前十名的產品。
     * 
     * @return array<int, array<string, mixed>>
     */
    public function getPopularProducts()
    {
        $products = $this->productRepository->getAllProducts();
        $products = collect($products)
            ->sortByDesc(self::DEFAULT_SORT_FIELD)
            ->values()
            ->take(self::DEFAULT_ROW_COUNT)
            ->all();
        return $products;
    }
}
