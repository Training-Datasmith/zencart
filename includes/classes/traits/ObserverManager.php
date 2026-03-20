<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
namespace Zencart\Traits;

use Zencart\Events\Event_Dto;
/**
 * @since ZC v1.5.8
 */
trait Observer_Manager
{
    private static array $deprecated_notifications = ['NOTIFY_GET_PRODUCT_DETAILS' => 'NOTIFY_GET_PRODUCT_OBJECT_DETAILS'];
    /**
     * Attach an observer to the notifier object
     * ("Subscribe" in pub/sub terminology, or "listener" in "event listener" terminology)
     *
     * NB. We have to get a little sneaky here to stop session based classes adding events ad infinitum
     * To do this we first concatenate the class name with the event id, as a class is only ever going to attach to an
     * event id once, this provides a unique key. To ensure there are no naming problems with the array key, we md5 the
     * unique name to provide a unique hashed key.
     *
     * @param object $observer Reference to the observer class
     * @param array $eventIDArray Array of eventId's to observe
     * @since ZC v1.5.8
     */
    public function attach(&$observer, array $event_id_array): void
    {
        foreach ($event_id_array as $event_id) {
            // handle deprecations
            if (array_key_exists($event_id, self::$deprecated_notifications)) {
                trigger_error("Use of deprecated notification '{$event_id}'.  Consider using '" . self::$deprecated_notifications[$event_id] . "' instead.", E_USER_WARNING);
                continue;
            }
            // handle attach
            $name_hash = hash('md5', $observer::class . $event_id);
            Event_Dto::get_instance()->set_observer($name_hash, ['obs' => &$observer, 'eventID' => $event_id]);
        }
    }
    /**
     * Detach an observer from the notifier object
     *
     * @param object $observer
     * @since ZC v1.5.8
     */
    public function detach($observer, array $event_id_array): void
    {
        foreach ($event_id_array as $event_id) {
            $name_hash = hash('md5', $observer::class . $event_id);
            Event_Dto::get_instance()->remove_observer($name_hash);
        }
    }
    /**
     * @since ZC v2.1.0
     */
    public function register_deprecated_event(string $old_event_id, string $new_event_id): void
    {
        if (array_key_exists($old_event_id, self::$deprecated_notifications)) {
            return;
        }
        self::$deprecated_notifications[$old_event_id] = $new_event_id;
    }
}