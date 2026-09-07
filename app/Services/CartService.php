<?php

namespace App\Services;

use App\Stores\CartStore;
use App\Repositories\ProductVariantRepository;
use Throwable;

class CartService
{
    /**
     * 建構子
     *
     * @return void
     */
    public function __construct(
        private CartStore $cartStore,
        private ProductVariantRepository $productVariantRepository,
    ) {}

    /**
     * 取得會員購物車內容，並附上各品項的產品名稱與圖片
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCartItems(int $memberId): array
    {
        $items = $this->cartStore->getItems($memberId);

        if ($items === []) {
            return [];
        }

        $variants = $this->productVariantRepository->findManyWithProductImages(array_column($items, 'product_variant_id'));

        return array_map(function (array $item) use ($variants) {
            $product = $variants->get($item['product_variant_id'])?->product;

            $item['product_name'] = $product?->name;
            $item['product_image_path'] = $product?->images->first()?->url ?? '/images/products/default.svg';

            return $item;
        }, $items);
    }

    /**
     * 將產品加入會員購物車
     *
     * @return array<string, mixed>
     */
    public function storeCartItem(int $memberId, int $productVariantId, int $quantity): array
    {
        $storedItemInfo = $this->cartStore->storeItem($memberId, $productVariantId, $quantity);

        if ($storedItemInfo === false) {
            return [
                'status' => 503,
                'message' => '產品加入會員購物車失敗，請稍後再試。',
            ];
        }

        return [
            'status' => 201,
            'message' => '產品已加入購物車。',
            'data' => $storedItemInfo,
        ];
    }

    /**
     * 更新會員購物車中指定產品的數量
     *
     * @return array<string, mixed>
     */
    public function updateCartItem(int $memberId, int $productVariantId, int $quantity): array
    {
        $updatedItemInfo = $this->cartStore->updateItem($memberId, $productVariantId, $quantity);

        if ($updatedItemInfo === false) {
            return [
                'status' => 503,
                'message' => '會員購物車產品數量更新失敗，請稍後再試。',
            ];
        }

        if ($updatedItemInfo === null) {
            return [
                'status' => 404,
                'message' => '會員購物車中找不到指定產品。',
            ];
        }

        return [
            'status' => 200,
            'message' => '購物車產品數量已更新。',
            'data' => $updatedItemInfo,
        ];
    }

    /**
     * 刪除會員購物車中指定的產品
     *
     * @return array<string, mixed>
     */
    public function destroyCartItem(int $memberId, int $productVariantId): array
    {
        $destroyedItemInfo = $this->cartStore->destroyItem($memberId, $productVariantId);

        if ($destroyedItemInfo === false) {
            return [
                'status' => 503,
                'message' => '會員購物車產品刪除失敗，請稍後再試。',
            ];
        }

        if ($destroyedItemInfo === null) {
            return [
                'status' => 404,
                'message' => '會員購物車中找不到指定產品。',
            ];
        }

        return [
            'status' => 200,
            'message' => '購物車產品已刪除。',
            'data' => $destroyedItemInfo,
        ];
    }

    /**
     * 清空會員購物車
     *
     * @return array<string, mixed>
     */
    public function clearCart(int $memberId): array
    {
        try {
            $this->cartStore->clearCart($memberId);

            return [
                'status' => 200,
                'message' => '購物車已清空。',
            ];
        } catch (Throwable $e) {
            return [
                'status' => 503,
                'message' => '會員購物車清空失敗，請稍後再試。',
            ];
        }
    }
}
