<?php

declare (strict_types=1);
/**
 * File contains just the base class
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 * @since ZC v1.3.0
 */
use Zencart\Traits\Notifier_Manager;
use Zencart\Traits\Observer_Manager;
class base
{
    use Notifier_Manager;
    use Observer_Manager;
    /**
     * @since ZC v1.5.2
     */
    public static function camelize($raw_name, $camel_first = false): null|false|int|float|string|array
    {
        if ($raw_name == '') {
            return $raw_name;
        }
        if ($camel_first) {
            $raw_name[0] = strtoupper((string) $raw_name[0]);
        }
        return preg_replace_callback('/[_-]([0-9,a-z])/', fn($matches): string => strtoupper((string) $matches[1]), (string) $raw_name);
    }
}