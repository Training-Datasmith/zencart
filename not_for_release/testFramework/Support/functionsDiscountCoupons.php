<?php

declare(strict_types=1);
/**
 * Stubbed Function
 *
 *
 */
function is_product_valid(): bool
{
    return true;
}

/**
 * Stubbed Function
 */
function is_coupon_valid_for_sales(): bool
{
    return true;
}

/**
 * Stubbed Function
 *
 * @param $value
 * @param $precision
 * @return float
 */
function zen_round($value, $precision)
{
    $value = round($value * 10 ** $precision, 0);

    return $value / 10 ** $precision;
}
