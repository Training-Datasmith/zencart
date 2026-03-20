<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 29 Modified in v2.2.0 $
 */
namespace Zencart\Traits;

use Zencart\Events\Event_Dto;
/**
 * @since ZC v1.5.8
 */
trait Notifier_Manager
{
    /**
     * Array of notifier aliases (where Notifier hook names have been renamed, such as for minor misspellings)
     * In your own application code, you may add to this list by calling $this->registerObserverAlias($old,$new)
     * @since ZC v1.5.8
     */
    private array $observer_aliases = ['NOTIFIY_ORDER_CART_SUBTOTAL_CALCULATE' => 'NOTIFY_ORDER_CART_SUBTOTAL_CALCULATE', 'NOTIFY_ADMIN_INVOIVE_HEADERS_AFTER_TAX' => 'NOTIFY_ADMIN_INVOICE_HEADERS_AFTER_TAX'];
    public function get_registered_observers(): array
    {
        return Event_Dto::get_instance()->get_observers();
    }
    /**
     * Notify observers that an event has occurred in the notifier object
     * ("Publish" in pub/sub terminology, or "fire event" in "event listener" terminology)
     *
     * Can optionally pass parameters and variables to the observer, useful for passing stuff which is outside of the 'scope' of the observed class.
     * Any of params 2-9 can be passed by reference, and will be updated in the calling location if the observer "update" function also receives them by reference
     *
     * @param string $eventID The event ID to notify/publish.
     * @param mixed|array|null $param1 passed as value only. Usually an array of data, or just a variable, or null if unused.
     * @param mixed|null $param2 passed by reference.
     * @param mixed|null $param3 passed by reference.
     * @param mixed|null $param4 passed by reference.
     * @param mixed|null $param5 passed by reference.
     * @param mixed|null $param6 passed by reference.
     * @param mixed|null $param7 passed by reference.
     * @param mixed|null $param8 passed by reference.
     * @param mixed|null $param9 passed by reference.
     *
     * NOTE: The $param1 is not received-by-reference, but params 2-9 are.
     * NOTE: The $param1 value CAN be an array, and is sometimes typecast to be an array, but can also safely be a string or int etc if the notifier sends such and the observer class expects same.
     * @since ZC v1.5.8
     */
    public function notify(string $event_id, mixed $param1 = [], mixed &$param2 = null, mixed &$param3 = null, mixed &$param4 = null, mixed &$param5 = null, mixed &$param6 = null, mixed &$param7 = null, mixed &$param8 = null, mixed &$param9 = null): void
    {
        // first log that the notifier was triggered:
        $this->log_notifier($event_id, $param1, $param2, $param3, $param4, $param5, $param6, $param7, $param8, $param9);
        $observers = $this->get_registered_observers();
        if (empty($observers)) {
            return;
        }
        foreach ($observers as $obs) {
            // identify the event
            $actual_event_id = $event_id;
            $match_map = [$event_id, '*'];
            // Adjust for aliases
            // if the event fired by the notifier is old and has an alias registered
            $has_alias = $this->event_id_has_alias($obs['eventID']);
            if ($has_alias) {
                // then lookup the correct new event name
                $event_alias = $this->substitute_alias($event_id);
                // use the substituted event name in the list of matches
                $match_map = [$event_alias, '*'];
                // and set the Actual event to the name that was originally attached to in the observer class
                $actual_event_id = $obs['eventID'];
            }
            // check whether the looped observer's eventID is a match to the event or alias
            if (!in_array($obs['eventID'], $match_map, true)) {
                continue;
            }
            // Notify the listening observers that this event has been triggered
            $methods_to_check = [];
            // Check for a snake_cased method name of the notifier Event, ONLY IF it begins with "NOTIFY_" or "NOTIFIER_"
            $snake_case_method = strtolower((string) $actual_event_id);
            if (preg_match('/^notif(y|ier)_/', $snake_case_method) && method_exists($obs['obs'], $snake_case_method)) {
                $methods_to_check[] = $snake_case_method;
            }
            // alternates are a camelCased version starting with "update" ie: updateNotifierNameCamelCased(), or just "update()"
            $methods_to_check[] = 'update' . \base::camelize(strtolower((string) $actual_event_id), true);
            $methods_to_check[] = 'update';
            foreach ($methods_to_check as $method) {
                if (method_exists($obs['obs'], $method)) {
                    $obs['obs']->{$method}($this, $actual_event_id, $param1, $param2, $param3, $param4, $param5, $param6, $param7, $param8, $param9);
                    continue 2;
                }
            }
            // If no update handler method exists then trigger an error so the problem is logged
            $class_name = is_object($obs['obs']) ? $obs['obs']::class : $obs['obs'];
            trigger_error('WARNING: No update() method (or matching alternative) found in the ' . $class_name . ' class for event ' . $actual_event_id, E_USER_WARNING);
        }
    }
    /**
     * @since ZC v1.5.8
     */
    protected function log_notifier(string $event_id, $param1, $param2, $param3, $param4, $param5, $param6, $param7, $param8, $param9): void
    {
        if (!defined('NOTIFIER_TRACE') || empty(NOTIFIER_TRACE) || NOTIFIER_TRACE === 'false' || NOTIFIER_TRACE === 'Off') {
            return;
        }
        if (defined('NOTIFIER_TRACE_EVENTS') && is_array(NOTIFIER_TRACE_EVENTS) && !in_array($event_id, NOTIFIER_TRACE_EVENTS)) {
            return;
        }
        global $zc_date;
        $file = DIR_FS_LOGS . '/notifier_trace.log';
        $param_array = is_array($param1) && count($param1) === 0 ? [] : ['param1' => $param1];
        for ($i = 2; $i < 10; $i++) {
            $param_n = "param{$i}";
            if (${$param_n} !== null) {
                $param_array[$param_n] = ${$param_n};
            }
        }
        global $this_is_home_page, $PHP_SELF;
        $main_page = IS_ADMIN_FLAG ? basename((string) $PHP_SELF) : $_GET['main_page'] ?? '';
        if (!empty($this_is_home_page)) {
            $main_page = 'index-home';
        }
        $output = '';
        if (count($param_array)) {
            $output = ', ';
            if (NOTIFIER_TRACE === 'var_export' || NOTIFIER_TRACE === 'var_dump' || NOTIFIER_TRACE === 'true') {
                $output .= var_export($param_array, true);
            } elseif (NOTIFIER_TRACE === 'print_r' || NOTIFIER_TRACE === 'On' || NOTIFIER_TRACE === true) {
                $output .= print_r($param_array, true);
            }
        }
        error_log($zc_date->output('%Y-%m-%d %H:%M:%S') . ' [main_page=' . $main_page . '] ' . $event_id . $output . "\n", 3, $file);
    }
    /**
     * @since ZC v1.5.8
     */
    private function event_id_has_alias($event_id): bool
    {
        return array_key_exists($event_id, $this->observer_aliases);
    }
    /**
     * @since ZC v1.5.8
     */
    private function substitute_alias($event_id): bool|int|string
    {
        return array_search($event_id, $this->observer_aliases, true);
    }
    /**
     * @since ZC v2.1.0
     */
    public function register_observer_alias(string $old_event_id, string $new_event_id): void
    {
        if ($this->event_id_has_alias($old_event_id)) {
            return;
        }
        $this->observer_aliases[$old_event_id] = $new_event_id;
    }
}