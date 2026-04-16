<?php

use Illuminate\Support\Facades\Schedule;

// 將產品的被瀏覽次數從 Redis 同步到資料庫中
Schedule::command('app:sync-product-view-counts')->hourly();
