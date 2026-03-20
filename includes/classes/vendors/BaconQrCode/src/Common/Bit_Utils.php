<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Common;

/**
 * General bit utilities.
 *
 * All utility methods are based on 32-bit integers and also work on 64-bit
 * systems.
 */
final class Bit_Utils
{
    private function __construct()
    {
    }
    /**
     * Performs an unsigned right shift.
     *
     * This is the same as the unsigned right shift operator ">>>" in other
     * languages.
     */
    public static function unsigned_right_shift(int $a, int $b): int
    {
        return $a >= 0 ? $a >> $b : ($a & 0x7fffffff) >> $b | 0x40000000 >> $b - 1;
    }
    /**
     * Gets the number of trailing zeros.
     */
    public static function number_of_trailing_zeros(int $i): int
    {
        $last_pos = strrpos(str_pad(decbin($i), 32, '0', STR_PAD_LEFT), '1');
        return $last_pos === false ? 32 : 31 - $last_pos;
    }
}