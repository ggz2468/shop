<?php

namespace App\Enums\Order;

enum Status: int
{
    /**
     * 已取消
     */
    case CANCELED = 1;

    /**
     * 已完成
     */
    case COMPLETED = 2;

    /**
     * 產品備貨中
     */
    case STOCKING = 3;

    /**
     * 產品配送中
     */
    case DELIVERING = 4;

    /**
     * 產品已送達
     */
    case DELIVERED = 5;
}
