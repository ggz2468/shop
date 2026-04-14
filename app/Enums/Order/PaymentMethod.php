<?php

namespace App\Enums\Order;

enum PaymentMethod: int
{
    /**
     * 信用卡
     */
    case CREDIT_CARD = 1;

    /**
     * 現金
     */
    case CASH = 2;

    /**
     * Line Pay
     */
    case LINE_PAY = 3;
}
