<?php

declare(strict_types=1);
/**
 * polyfills to accommodate older/newer PHP versions, adapted from https://github.com/symfony/polyfill/
 * @copyright Portions (c) 2015-present Fabien Potencier <fabien@symfony.com>
 * @version $Id: DrByte 2025 Sep 19 Modified in v2.2.0 $
 * @since ZC v1.5.7c
 */

/* These polyfills are safe to load all the time, as they simply add PHP functions.
 * Since v1.5.7c you can use this file to replace /includes/functions/php_polyfills.php and it will support both admin and non-admin.
 *
 * Before v1.5.7c, you would need to copy this file into both of the following places to get the same support.
 * - /admin/includes/extra_configures/php_polyfills.php
 * - /includes/extra_configures/php_polyfills.php
 */

/* LICENSE
 *
 * Copyright (c) 2015-present Fabien Potencier
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

if (\PHP_VERSION_ID >= 80500) {
    return;
}

if (!function_exists('get_error_handler')) {
    function get_error_handler(): ?callable
    {
        $handler = set_error_handler(null);
        restore_error_handler();

        return $handler;
    }
}

if (!function_exists('get_exception_handler')) {
    function get_exception_handler(): ?callable
    {
        $handler = set_exception_handler(null);
        restore_exception_handler();

        return $handler;
    }
}

if (!function_exists('array_first')) {
    function array_first(array $array)
    {
        foreach ($array as $value) {
            return $value;
        }

        return null;
    }
}

if (!function_exists('array_last')) {
    function array_last(array $array)
    {
        return $array ? current(array_slice($array, -1)) : null;
    }
}
return;
