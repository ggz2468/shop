<?php

namespace App\Repositories;

use App\Models\OrderDetail;

class OrderDetailRepository extends Repository
{
    /**
     * 新增多筆訂單明細
     * 
     * @param array<int, array<string, mixed>> $data
     * @return bool
     */
    public function createMany(array $data): bool
    {
        return OrderDetail::insert($data);
    }
}
