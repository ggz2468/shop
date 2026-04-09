<?php

namespace App\Enums\Order;

class Status
{
    /**
     * 已取消
     * 
     * @var int
     */
    public const CANCELED = 1;

    /**
     * 已完成
     * 
     * @var int
     */
    public const COMPLETED = 2;

    /**
     * 產品備貨中
     * 
     * @var int
     */
    public const STOCKING = 3;

    /**
     * 產品配送中
     * 
     * @var int
     */
    public const DELIVERING = 4;

    /**
     * 產品已送達
     * 
     * @var int
     */
    public const DELIVERED = 5;
}
