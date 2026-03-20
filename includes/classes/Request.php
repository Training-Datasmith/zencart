<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
namespace Zencart\Request;

use Zencart\Traits\Singleton;
/**
 * @since ZC v1.5.8
 */
class Request
{
    use Singleton;
    protected $param_bag;
    /**
     * @return mixed|Request
     * @since ZC v1.5.8
     */
    public static function capture()
    {
        $self = self::get_instance();
        $self->param_bag = $_REQUEST;
        return self::get_instance();
    }
    /**
     * @param $key
     * @return mixed|null
     * @since ZC v1.5.8
     */
    public function input($key, $default = null)
    {
        return $this->param_bag[$key] ?? $default;
    }
    /**
     * @param $key
     * @since ZC v1.5.8
     */
    public function has($key): bool
    {
        return isset($this->param_bag[$key]);
    }
}