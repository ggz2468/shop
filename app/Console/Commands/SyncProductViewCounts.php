<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\ProductViewCount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class SyncProductViewCounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-product-view-counts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '將產品的被瀏覽次數從 Redis 同步到資料庫中';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 從 Redis 獲取產品的被瀏覽次數
        $productViewCounts = Redis::zrange('product_view_counts', 0, -1, ['withscores' => true]);
        $recordedAt = now()->startOfHour();

        DB::transaction(function () use ($productViewCounts, $recordedAt) {
            // 將被瀏覽次數同步到資料庫
            foreach ($productViewCounts as $productId => $viewCounts) {
                // 更新產品被瀏覽次數到資料庫
                Product::where('id', $productId)->increment('view_counts', (int) $viewCounts);

                // 取得或新增產品被瀏覽次數資料
                $productViewCount = ProductViewCount::firstOrCreate(
                    [
                        'product_id' => $productId,
                        'recorded_at' => $recordedAt,
                    ],
                    [
                        'view_counts' => 0,
                    ]
                );

                // 額外新增產品被瀏覽次數與紀錄時間到資料庫中，以便後續分析使用
                $productViewCount->increment('view_counts', (int) $viewCounts);
            }
        });

        // 將所有產品資料的 Cache 清除，以確保下一次讀取時能從資料庫獲取最新的被瀏覽次數
        Cache::tags(['products'])->flush();

        // 清除 Redis 中的產品被瀏覽次數資料
        Redis::del('product_view_counts');
    }
}
