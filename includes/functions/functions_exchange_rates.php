<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
/**
 * Dependencies:
 * - DB class must be instantiated as $db (done normally by application_top)
 * - if any additional currency-update plugins are installed, those plugins' functions must be loaded (done normally by admin application_top)
 * NOTE: admin application_top cannot be loaded successfully without an admin login ID.
 * @since ZC v1.5.5
 */
function zen_update_currencies(bool $output_messages_to_command_line = false): void
{
    global $db, $message_stack, $zco_notifier;
    @set_time_limit(600);
    $results = $db->Execute('SELECT currencies_id, code, title, decimal_places FROM ' . TABLE_CURRENCIES);
    foreach ($results as $result) {
        $server_used = CURRENCY_SERVER_PRIMARY;
        $rate = '';
        $quote_function = 'quote_' . CURRENCY_SERVER_PRIMARY . '_currency';
        if (function_exists($quote_function)) {
            $rate = $quote_function($result['code']);
        }
        if (empty($rate) && !empty(CURRENCY_SERVER_BACKUP)) {
            // failed to get currency quote from primary server - attempting to use backup server instead
            $msg = sprintf(WARNING_PRIMARY_SERVER_FAILED, CURRENCY_SERVER_PRIMARY, $result['title'], $result['code']);
            if (is_object($message_stack)) {
                $message_stack->add_session($msg, 'warning');
            } elseif ($output_messages_to_command_line) {
                echo "{$msg}\n";
            }
            $quote_function = 'quote_' . CURRENCY_SERVER_BACKUP . '_currency';
            if (function_exists($quote_function)) {
                $rate = $quote_function($result['code']);
            }
            $server_used = CURRENCY_SERVER_BACKUP;
        }
        if (!empty($rate)) {
            /* Add currency uplift, because exchange rates quoted aren't always the same as what your own bank gives you */
            $multiplier = defined('CURRENCY_UPLIFT_RATIO') && (int) CURRENCY_UPLIFT_RATIO != 0 ? CURRENCY_UPLIFT_RATIO : 0;
            $zco_notifier->notify('ADMIN_CURRENCY_EXCHANGE_RATE_MULTIPLIER', $result['code'], $multiplier, $rate);
            if ($rate != 1 && $multiplier > 0) {
                $rate = (string) ((float) $rate * (float) $multiplier);
            }
            // special handling for currencies which don't support decimal places; intentionally loosely typed
            if ($result['decimal_places'] == '0') {
                $rate = (int) $rate;
            }
            if (!empty($rate)) {
                $zco_notifier->notify('ADMIN_CURRENCY_EXCHANGE_RATE_SINGLE', $result['code'], $rate);
                $db->Execute('UPDATE ' . TABLE_CURRENCIES . "\n                      SET value = '" . round((float) $rate, 8) . "', last_updated = now()\n                      WHERE currencies_id = '" . (int) $result['currencies_id'] . "'");
                $msg = sprintf(TEXT_INFO_CURRENCY_UPDATED, $result['title'], $result['code'], round((float) $rate, 8), $server_used);
                if (is_object($message_stack)) {
                    $message_stack->add_session($msg, 'success');
                } elseif ($output_messages_to_command_line) {
                    echo "{$msg}\n";
                }
            } else {
                $msg = sprintf(ERROR_CURRENCY_INVALID, $result['title'], $result['code'], $server_used);
                if (is_object($message_stack)) {
                    $message_stack->add_session($msg, 'error');
                } elseif ($output_messages_to_command_line) {
                    echo "{$msg}\n";
                }
            }
        }
    }
    if (function_exists('zen_record_admin_activity')) {
        zen_record_admin_activity('Currency exchange rates updated: ' . $msg, 'info');
    }
    $zco_notifier->notify('ADMIN_CURRENCY_EXCHANGE_RATES_UPDATED', $msg);
}
/**
 * ECB Rates - based on data format in July 2017
 *
 * @param string $currencyCode requested
 * @param string $base currency code
 * @return int
 * @since ZC v1.5.0
 */
function quote_ecb_currency(string $currency_code = '', string $base = DEFAULT_CURRENCY): int|string
{
    if ($currency_code === $base) {
        return 1;
    }
    static $xml_content = [];
    $url = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';
    if (empty($xml_content)) {
        $xml_content = @file($url);
        if (empty($xml_content)) {
            $xml_content = zen_do_curl_request($url, 'GET');
            $xml_content = explode("\n", $xml_content);
        }
    }
    $currency_array = [];
    $currency_array['EUR'] = 1;
    $line = '';
    foreach ($xml_content as $line) {
        if (!preg_match("/currency='([[:alpha:]]+)'/", (string) $line, $reg)) {
            continue;
        }
        if (!preg_match("/rate='([[:graph:]]+)'/", (string) $line, $rate_val)) {
            continue;
        }
        $currency_array[$reg[1]] = (float) $rate_val[1];
    }
    // Check for valid data
    if (empty($currency_array[$base]) || !isset($currency_array[$currency_code])) {
        return '';
        // no valid value, so abort, else risk divide-by-zero
    }
    return (string) ((float) $currency_array[$currency_code] / $currency_array[$base]);
}
/**
 * BOC Rates - based on data format in July 2017
 *
 * @param string $currencyCode requested
 * @param string $base currency code
 * @return bool
 * @since ZC v1.5.0
 */
function quote_boc_currency(string $currency_code = '', string $base = DEFAULT_CURRENCY): bool|int|string
{
    if ($currency_code === $base) {
        return 1;
    }
    $requested = $currency_code;
    $url = 'https://www.bankofcanada.ca/valet/observations/group/FX_RATES_DAILY/json';
    static $bo_cdata = [];
    if (empty($bo_cdata)) {
        $result = zen_do_curl_request($url, 'GET');
        if (empty($result)) {
            return false;
        }
        $bo_cdata = json_decode($result, true);
        // no data means unable to continue with updates
        if (empty($bo_cdata) || empty($bo_cdata['observations'])) {
            return false;
        }
    }
    // grab the last date data reported
    $values = array_pop($bo_cdata['observations']);
    // if nothing found, attempt to get the next-last item
    if (empty($values)) {
        $values = array_pop($bo_cdata['observations']);
    }
    if (empty($values) || !is_array($values)) {
        return false;
    }
    $lookup = 'FX' . strtoupper($requested) . 'CAD';
    $default = 'FX' . strtoupper($base) . 'CAD';
    $values['FXCADCAD']['v'] = 1;
    // quoting BOC, where CAD is always = 1
    if (!empty($values[$default]['v']) && !empty($values[$lookup]['v'])) {
        return (string) ($values[$default]['v'] / $values[$lookup]['v']);
    }
    return false;
}
/**
 * @since ZC v1.3.5
 */
function do_curl_currency_request(string $method, string $url, string|array|null $vars = ''): string
{
    return zen_do_curl_request($url, $method, $vars);
}