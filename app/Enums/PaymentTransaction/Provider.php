<?php

namespace App\Enums\PaymentTransaction;

enum Provider: int
{
    /**
     * 綠界金流
     */
    case ECPAY = 1;

    /**
     * 藍新金流
     */
    case NEWEBPAY = 2;

    /**
     * LINE Pay
     */
    case LINE_PAY = 3;

    /**
     * 現金付款
     */
    case CASH = 4;
}
