<?php

namespace App\Services;

use App\Stores\CartStore;

class CartService
{
    /**
     * 建構子
     * 
     * @param \App\Stores\CartStore $cartStore
     * @return void
     */
    public function __construct(
        private CartStore $cartStore,
    ) {
        
    }

    /**
     * 取得會員購物車內容
     *
     * @param int $memberId
     * @return array<int, array<string, mixed>>
     */
    public function getCartItems(int $memberId): array
    {
        return $this->cartStore->getItems($memberId);
    }

    /**
     * 將產品加入會員購物車
     *
     * @param int $memberId
     * @param int $productId
     * @param int $quantity
     * @return array<string, mixed>
     */
    public function storeCartItem(int $memberId, int $productId, int $quantity): array
    {
        $storedItemInfo = $this->cartStore->storeItem($memberId, $productId, $quantity);

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
}
