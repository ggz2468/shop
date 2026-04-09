<?php

namespace App\Enums\Order;

class PaymentMethod
{
    /**
     * 信用卡
     * 
     * @var int
     */
    public const CREDIT_CARD = 1;

    /**
     * 現金
     * 
     * @var int
     */
    public const CASH = 2;

    /**
     * Line Pay
     * 
     * @var int
     */
    public const LINE_PAY = 3;
}
