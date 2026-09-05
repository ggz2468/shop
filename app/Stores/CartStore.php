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
     * @return void
     */
    public function __construct(
        private CacheRepository $cache,
        private LoggerInterface $logger,
    ) {}

    /**
     * 取得會員購物車內容
     *
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
     * @return array<string, mixed>|false
     */
    public function storeItem(int $memberId, int $productVariantId, int $quantity): array|false
    {
        try {
            $storedItem = [
                'product_variant_id' => $productVariantId,
                'quantity' => $quantity,
            ];
            $cacheKey = $this->cacheKey($memberId);
            $existingItems = $this->getItems($memberId);

            // 若不存在相同產品，則新增項目
            if (! in_array($productVariantId, array_column($existingItems, 'product_variant_id'), true)) {
                $existingItems[] = $storedItem;

                if ($this->cache->put($cacheKey, array_values($existingItems), $this->cartExpiresAt()) === false) {
                    throw new RuntimeException('產品加入會員購物車失敗');
                }

                return $storedItem;
            }

            // 更新會員購物車中產品數量
            foreach ($existingItems as &$item) {
                if ($item['product_variant_id'] !== $storedItem['product_variant_id']) {
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
                'product_variant_id' => $productVariantId,
                'quantity' => $quantity,
                'exception' => $e,
            ]);

            return false;
        }
    }

    /**
     * 更新會員購物車中指定產品的數量
     *
     * @return array<string, mixed>|false|null
     */
    public function updateItem(int $memberId, int $productVariantId, int $quantity): array|false|null
    {
        try {
            $updatedItem = [
                'product_variant_id' => $productVariantId,
                'quantity' => $quantity,
            ];
            $cacheKey = $this->cacheKey($memberId);
            $existingItems = $this->getItems($memberId);

            // 檢查是否存在相同產品
            if (! in_array($productVariantId, array_column($existingItems, 'product_variant_id'), true)) {
                return null;
            }

            // 更新指定產品的數量
            foreach ($existingItems as &$item) {
                if ($item['product_variant_id'] !== $updatedItem['product_variant_id']) {
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
                'product_variant_id' => $productVariantId,
                'quantity' => $quantity,
                'exception' => $e,
            ]);

            return false;
        }
    }

    /**
     * 刪除會員購物車中指定的產品
     *
     * @return array<string, mixed>|false|null
     */
    public function destroyItem(int $memberId, int $productVariantId): array|false|null
    {
        try {
            $destroyedItem = [
                'product_variant_id' => $productVariantId,
            ];
            $cacheKey = $this->cacheKey($memberId);
            $existingItems = $this->getItems($memberId);

            // 檢查是否存在相同產品
            if (! in_array($productVariantId, array_column($existingItems, 'product_variant_id'), true)) {
                return null;
            }

            // 刪除指定產品
            foreach ($existingItems as $index => $item) {
                if ($item['product_variant_id'] !== $destroyedItem['product_variant_id']) {
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
                'product_variant_id' => $productVariantId,
                'exception' => $e,
            ]);

            return false;
        }
    }

    /**
     * 清空會員購物車
     *
     *
     * @throws \RuntimeException
     */
    public function clearCart(int $memberId): void
    {
        try {
            $this->cache->forget($this->cacheKey($memberId));
        } catch (Throwable $e) {
            $this->logger->error('清空會員購物車失敗', [
                'member_id' => $memberId,
                'exception' => $e,
            ]);
            throw new RuntimeException('清空會員購物車失敗', previous: $e);
        }
    }

    private function cacheKey(int $memberId): string
    {
        return self::CACHE_KEY_PREFIX.$memberId.':items';
    }

    private function cartExpiresAt(): Carbon
    {
        return Carbon::now()->addDays(self::CACHE_TTL_DAYS);
    }
}
