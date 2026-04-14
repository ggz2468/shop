<?php

namespace App\Enums\ProductSpec;

enum Size: int
{
    /**
     * 特小
     */
    case XS = 1;

    /**
     * 小
     */
    case S = 2;

    /**
     * 中
     */
    case M = 3;

    /**
     * 大
     */
    case L = 4;

    /**
     * 特大
     */
    case XL = 5;
}
