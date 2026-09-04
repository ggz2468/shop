<?php

namespace App\Enums\Shipment;

enum Provider: int
{
    /**
     * 綠界物流
     */
    case ECPAY_LOGISTICS = 1;

    /**
     * 黑貓宅急便
     */
    case TCAT = 2;

    /**
     * 中華郵政
     */
    case POST = 3;
}