<?php

namespace App\Repositories;

use App\Models\ProductVariant;

class ProductVariantRepository extends Repository
{
    public function updateStockQuantity(int $productVariantId, int $quantity): int
    {
        return ProductVariant::query()
            ->where('id', $productVariantId)
            ->where('stock_quantity', '>=', $quantity)
            ->decrement('stock_quantity', $quantity);
    }
}
