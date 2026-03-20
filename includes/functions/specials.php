<?php

declare (strict_types=1);
/**
 * product-specials functions
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
/**
 * Set the status of a product on special
 * @since ZC v1.0.3
 */
function zen_set_specials_status($specials_id, $status)
{
    global $db;
    $sql = 'update ' . TABLE_SPECIALS . "\n            set status = '" . (int) $status . "', date_status_change = now()\n            where specials_id = '" . (int) $specials_id . "'";
    return $db->Execute($sql);
}
/**
 * Auto expire products on special
 * @since ZC v1.0.3
 */
function zen_expire_specials(): void
{
    global $db;
    $date_range = time();
    $zc_specials_date = date('Ymd', $date_range);
    $specials_query = 'select specials_id, products_id
                       from ' . TABLE_SPECIALS . "\n                       where status = '1'\n                       and ((" . $zc_specials_date . " >= expires_date and expires_date != '0001-01-01')\n                       or (" . $zc_specials_date . " < specials_date_available and specials_date_available != '0001-01-01'))";
    $specials = $db->Execute($specials_query);
    if ($specials->record_count() > 0) {
        while (!$specials->EOF) {
            zen_set_specials_status($specials->fields['specials_id'], '0');
            zen_update_products_price_sorter($specials->fields['products_id']);
            $specials->move_next();
        }
    }
}
/**
 * Auto start products on special
 * @since ZC v1.2.0d
 */
function zen_start_specials(): void
{
    global $db;
    $date_range = time();
    $zc_specials_date = date('Ymd', $date_range);
    // turn on special if active
    $specials_query = 'select specials_id, products_id
                       from ' . TABLE_SPECIALS . "\n                       where status = '0'\n                       and (((specials_date_available <= " . $zc_specials_date . " and specials_date_available != '0001-01-01') and (expires_date > " . $zc_specials_date . '))
                       or ((specials_date_available <= ' . $zc_specials_date . " and specials_date_available != '0001-01-01') and (expires_date = '0001-01-01'))\n                       or (specials_date_available = '0001-01-01' and expires_date > " . $zc_specials_date . '))
                       ';
    $specials = $db->Execute($specials_query);
    if ($specials->record_count() > 0) {
        while (!$specials->EOF) {
            zen_set_specials_status($specials->fields['specials_id'], '1');
            zen_update_products_price_sorter($specials->fields['products_id']);
            $specials->move_next();
        }
    }
    // turn off special if not active yet
    $specials_query = 'select specials_id, products_id
                       from ' . TABLE_SPECIALS . "\n                       where status = '1'\n                       and (" . $zc_specials_date . " < specials_date_available and specials_date_available != '0001-01-01')\n                       ";
    $specials = $db->Execute($specials_query);
    if ($specials->record_count() > 0) {
        while (!$specials->EOF) {
            zen_set_specials_status($specials->fields['specials_id'], '0');
            zen_update_products_price_sorter($specials->fields['products_id']);
            $specials->move_next();
        }
    }
}