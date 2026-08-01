<?php

namespace App\Stores;

use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class CartStore
{
    /**
     * 購物車 cache key 前綴字串
     * 
     * @var string
     */
    private const string CACHE_KEY_PREFIX = 'cart:member:';

    /**
     * 購物車 cache 有效天數
     * 
     * @var int
     */
    private const int CACHE_TTL_DAYS = 7;

    /**
     * 建構子
     * 
     * @param \Illuminate\Contracts\Cache\Repository $cache
     * @param \Psr\Log\LoggerInterface $logger
     * @return void
     */
    public function __construct(
        private CacheRepository $cache,
        private LoggerInterface $logger,
    ) {
        
    }

    /**
     * 取得會員購物車內容
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
     * 將產品加入會員購物車
     *
     * @param int $memberId
     * @param int $productId
     * @param int $quantity
     * @return array<string, mixed>|false
     */
    public function storeItem(int $memberId, int $productId, int $quantity): array|false
    {
        try {
            $storedItem = [
                'product_id' => $productId,
                'quantity' => $quantity,
            ];
            $cacheKey = $this->cacheKey($memberId);
            $existingItems = $this->getItems($memberId);

            // 若不存在相同產品，則新增項目
            if (!in_array($productId, array_column($existingItems, 'product_id'), true)) {
                $existingItems[] = $storedItem;

                if ($this->cache->put($cacheKey, array_values($existingItems), $this->cartExpiresAt()) === false) {
                    throw new RuntimeException('產品加入會員購物車失敗');
                }

                return $storedItem;
            }

            // 更新會員購物車中產品數量
            foreach ($existingItems as &$item) {
                if ($item['product_id'] !== $storedItem['product_id']) {
                    continue;
                }
                
                $item['quantity'] += $storedItem['quantity'];
                break;
            }

            if ($this->cache->put($cacheKey, array_values($existingItems), $this->cartExpiresAt()) === false) {
                throw new RuntimeException('產品加入會員購物車失敗');
            }

            return $storedItem;
        } catch (Throwable $e) {
            $this->logger->error('產品加入會員購物車失敗', [
                'member_id' => $memberId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'exception' => $e,
            ]);
            return false;
        }
    }

    /**
     * 更新會員購物車中指定產品的數量
     * 
     * @param int $memberId
     * @param int $productId
     * @param int $quantity
     * @return array<string, mixed>|false|null
     */
    public function updateItem(int $memberId, int $productId, int $quantity): array|false|null
    {
        try {
            $updatedItem = [
                'product_id' => $productId,
                'quantity' => $quantity,
            ];
            $cacheKey = $this->cacheKey($memberId);
            $existingItems = $this->getItems($memberId);

            // 檢查是否存在相同產品
            if (!in_array($productId, array_column($existingItems, 'product_id'), true)) {
                return null;
            }

            // 更新指定產品的數量
            foreach ($existingItems as &$item) {
                if ($item['product_id'] !== $updatedItem['product_id']) {
                    continue;
                }

                $item['quantity'] = $updatedItem['quantity'];
                break;
            }

            if ($this->cache->put($cacheKey, array_values($existingItems), $this->cartExpiresAt()) === false) {
                throw new RuntimeException('更新會員購物車中指定產品的數量失敗');
            }

            return $updatedItem;
        } catch (Throwable $e) {
            $this->logger->error('更新會員購物車中指定產品的數量失敗', [
                'member_id' => $memberId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'exception' => $e,
            ]);
            return false;
        }
    }

    /**
     * 刪除會員購物車中指定的產品
     * 
     * @param int $memberId
     * @param int $productId
     * @return array<string, mixed>|false|null
     */
    public function destroyItem(int $memberId, int $productId): array|false|null
    {
        try {
            $destroyedItem = [
                'product_id' => $productId,
            ];
            $cacheKey = $this->cacheKey($memberId);
            $existingItems = $this->getItems($memberId);

            // 檢查是否存在相同產品
            if (!in_array($productId, array_column($existingItems, 'product_id'), true)) {
                return null;
            }

            // 刪除指定產品
            foreach ($existingItems as $index => $item) {
                if ($item['product_id'] !== $destroyedItem['product_id']) {
                    continue;
                }

                unset($existingItems[$index]);
                break;
            }

            if ($this->cache->put($cacheKey, array_values($existingItems), $this->cartExpiresAt()) === false) {
                throw new RuntimeException('刪除會員購物車中指定的產品失敗');
            }

            return $destroyedItem;
        } catch (Throwable $e) {
            $this->logger->error('刪除會員購物車中指定的產品失敗', [
                'member_id' => $memberId,
                'product_id' => $productId,
                'exception' => $e,
            ]);
            return false;
        }
    }

    /**
     * @param int $memberId
     * @return string
     */
    private function cacheKey(int $memberId): string
    {
        return self::CACHE_KEY_PREFIX . $memberId . ':items';
    }

    /**
     * @return \Carbon\Carbon
     */
    private function cartExpiresAt(): Carbon
    {
        return Carbon::now()->addDays(self::CACHE_TTL_DAYS);
    }
}
