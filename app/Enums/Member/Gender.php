<?php

namespace App\Enums\Member;

enum Gender: int
{
    /**
     * 男性
     */
    case Male = 1;

    /**
     * 女性
     */
    case Female = 2;

    /**
     * 其他
     */
    case Other = 3;
}
