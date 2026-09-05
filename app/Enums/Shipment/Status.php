<?php

namespace App\Enums\Shipment;

enum Status: int
{
    /**
     * 待建立物流單
     */
    case PENDING = 1;

    /**
     * 物流單已建立
     */
    case CREATED = 2;

    /**
     * 配送中
     */
    case SHIPPED = 3;

    /**
     * 已送達
     */
    case DELIVERED = 4;

    /**
     * 建立或配送失敗
     */
    case FAILED = 5;

    /**
     * 已退回
     */
    case RETURNED = 6;

    /**
     * 已取消
     */
    case CANCELED = 7;
}
