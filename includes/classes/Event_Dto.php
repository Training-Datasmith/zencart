<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
namespace Zencart\Events;

use Zencart\Traits\Singleton;
/**
 * @since ZC v1.5.8
 */
class Event_Dto
{
    use Singleton;
    private $observers = [];
    /**
     * @since ZC v1.5.8
     */
    public function get_observers()
    {
        return $this->observers;
    }
    /**
     * @since ZC v1.5.8
     */
    public function set_observer($event_hash, $event_parameters): void
    {
        $this->observers[$event_hash] = $event_parameters;
    }
    /**
     * @since ZC v1.5.8
     */
    public function remove_observer($event_hash): void
    {
        if (isset($this->observers[$event_hash])) {
            unset($this->observers[$event_hash]);
        }
    }
}