<?php

use Illuminate\Support\Facades\Schedule;

// 將產品的被瀏覽次數從 Redis 同步到資料庫中
Schedule::command('app:sync-product-view-counts')->hourly();

// 每月維護 product_view_counts 分區（補下月分區並刪除超過三個月前分區）
Schedule::command('app:maintain-product-view-counts-partitions')
	->monthlyOn(1, '00:05')
	->withoutOverlapping();
