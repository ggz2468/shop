<?php

namespace App\Repositories;

use App\Models\ProductVariant;

class ProductVariantRepository extends Repository
{
    /**
     * 更新產品規格的庫存數量
     */
    public function updateStockQuantity(int $productVariantId, int $quantity): int
    {
        return ProductVariant::query()
            ->where('id', $productVariantId)
            ->where('stock_quantity', '>=', $quantity)
            ->decrement('stock_quantity', $quantity);
    }

    /**
     * 依產品規格 ID 列表取得產品規格資料，並帶入其所屬產品與產品圖片
     *
     * @param  array<int, int>  $productVariantIds
     * @return \Illuminate\Support\Collection<int, \App\Models\ProductVariant>
     */
    public function findManyWithProductImages(array $productVariantIds)
    {
        return ProductVariant::query()
            ->with(['product.images'])
            ->whereIn('id', $productVariantIds)
            ->get()
            ->keyBy('id');
    }
}
