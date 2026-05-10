<?php

namespace App\Support;

final class OtpGenerator
{
    public static function generateNumericCode(int $length = 6): string
    {
        if ($length < 1) {
            throw new \InvalidArgumentException('OTP length must be at least 1.');
        }

        $max = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }
}
