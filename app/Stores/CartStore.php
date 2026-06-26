<?php

namespace App\Stores;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

class CartStore
{
    private const CACHE_KEY_PREFIX = 'cart:member:';

    /**
     * 建構子
     * 
     * @param \Illuminate\Contracts\Cache\Repository $cache
     * @return void
     */
    public function __construct(
        private CacheRepository $cache,
    ) {
        
    }

    /**
     * 取得會員購物車商品項目
     *
     * @param int $memberId
     * @return array<int, array<string, mixed>>
     */
    public function getItems(int $memberId): array
    {
        $items = $this->cache->get($this->cacheKey($memberId), []);

        return is_array($items) ? $items : [];
    }

    /**
     * @param int $memberId
     * @return string
     */
    private function cacheKey(int $memberId): string
    {
        return self::CACHE_KEY_PREFIX . $memberId . ':items';
    }
}
