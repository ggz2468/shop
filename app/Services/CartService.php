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
}
