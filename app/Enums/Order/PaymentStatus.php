<?php

namespace App\Enums\Order;

enum PaymentStatus: int
{
    /**
     * 未付款
     */
    case UNPAID = 1;

    /**
     * 付款處理中
     */
    case PENDING = 2;

    /**
     * 已付款
     */
    case PAID = 3;

    /**
     * 付款失敗
     */
    case FAILED = 4;

    /**
     * 已退款
     */
    case REFUNDED = 5;
}
