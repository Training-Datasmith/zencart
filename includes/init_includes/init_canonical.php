<?php

declare (strict_types=1);
/**
 * canonical link handling
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Leonard 2025 Feb 12 Modified in v2.2.0 $
 */
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}
// cPath is excluded by default
$include_c_path = false;
// EXCLUDE certain parameters which should not be included in canonical links:
// NOTE: These are alphabetized for readability.
$exclude_params = ['act', 'action', 'addr', 'authcapt', 'currency', 'delete', 'dfrom', 'dto', 'ec_cancel', 'edit', 'gclid', 'goback', 'goto', 'gv_no', 'inc_subcat', 'main_page', 'markflow', 'method', 'nocache', 'notify', 'order', 'override', 'pfrom', 'pos', 'products_tax_class_id', 'pto', 'referer', 'search_in_description', 'set_session_login', 'token', 'tx', 'type', 'typefilter', 'zenid', $zen_session_id];
// the following are listed one-per-line to allow for easy commenting-out in case a merchant wants to bypass these exclusions for canonical URL building
$exclude_params[] = 'disp_order';
$exclude_params[] = 'sort';
$exclude_params[] = 'alpha_filter_id';
$exclude_params[] = 'filter_id';
$exclude_params[] = 'utm_source';
$exclude_params[] = 'utm_medium';
$exclude_params[] = 'utm_content';
$exclude_params[] = 'utm_campaign';
$exclude_params[] = 'language';
$exclude_params[] = 'number_of_uploads';
if (isset($_GET['page']) && (!is_numeric($_GET['page']) || $_GET['page'] < 2)) {
    $exclude_params[] = 'page';
}
// The following are additional whitelisted params used for sanitizing the generated canonical URL (to prevent rogue params from getting added to canonical maliciously)
// NOTE: These are alphabetized for readability.
$keepable_params = ['categories_id', 'chapter', 'cID', 'cPath', 'faq_item', 'id', 'keyword', 'manufacturers_id', 'order_id', 'page', 'pid', 'pID', 'product_id', 'products_id', 'products_image_large_additional', 'reviews_id', 'typefilter'];
$keepable_params[] = 'record_company_id';
$keepable_params[] = 'music_genre_id';
$keepable_params[] = 'artists_id';
if ($current_page === FILENAME_SEARCH_RESULT) {
    $exclude_params = array_diff($exclude_params, ['search_in_description']);
    $keepable_params[] = 'search_in_description';
}
$zco_notifier->notify('NOTIFY_INIT_CANONICAL_PARAM_WHITELIST', $current_page, $exclude_params, $keepable_params, $include_c_path);
// Go thru all GET params and prepare list of potentially-rogue keys to not include in generated canonical URL
$rogues = [];
foreach ($_GET as $key => $val) {
    if (in_array($key, $exclude_params)) {
        continue;
        // these will already be stripped, so skip
    }
    if (in_array($key, $keepable_params)) {
        continue;
        // these are part of navigation etc, so we don't want to strip these, so skip
    }
    $exclude_params[] = $key;
    $rogues[$key] = $val;
    // this is here as an aid to finding false-positives. Simply uncomment the next line (if sizeof(rogues)) to cause rogues to be output in /logs/myDebug-xxxx.log for review
}
//if (sizeof($rogues)) error_log('Rogue $_GET params, from IP address: ' . $_SERVER['REMOTE_ADDR'] . ($_SERVER['HTTP_REFERER'] != '' ? "\nReferrer: " . $_SERVER['HTTP_REFERER'] : '') . "\nURI=" . $_SERVER['REQUEST_URI'] . "\n" . print_r($rogues, true));
$canonical_link = '';
switch (true) {
    /**
     * for products (esp those linked to multiple categories):
     */
    case $current_page !== FILENAME_PRODUCT_REVIEWS_INFO && str_ends_with((string) $current_page, '_info') && isset($_GET['products_id']):
        $canonical_link = zen_href_link($current_page, ($include_c_path ? 'cPath=' . zen_get_generated_category_path_rev(zen_get_products_category_id($_GET['products_id'])) . '&' : '') . 'products_id=' . $_GET['products_id'], 'NONSSL', false);
        break;
    /**
     * for product listings (ie: "categories"):
     */
    case $current_page === FILENAME_DEFAULT && isset($_GET['cPath']):
    /**
     * for all/new/special/featured listings:
     */
    // no break
    case in_array($current_page, [FILENAME_FEATURED_PRODUCTS, FILENAME_SPECIALS, FILENAME_PRODUCTS_NEW, FILENAME_PRODUCTS_ALL]):
    /**
     * for manufacturer listings:
     */
    // no break
    case $current_page === FILENAME_DEFAULT && isset($_GET['manufacturers_id']):
    /**
     * for ez-pages:
     */
    // no break
    case $current_page === FILENAME_EZPAGES && isset($_GET['id']):
        $canonical_link = zen_href_link($current_page, zen_get_all_get_params($exclude_params), 'NONSSL', false);
        // alternate way, depending on specialized site needs:
        //    $canonicalLink = zen_href_link($current_page,'cPath=' . zen_get_generated_category_path_rev($current_category_id) , 'NONSSL', false);
        break;
    /**
     * For specific product reviews
     */
    case $current_page === FILENAME_PRODUCT_REVIEWS_INFO && !empty($_GET['products_id']) && !empty($_GET['reviews_id']):
        $canonical_link = zen_href_link($current_page, 'products_id=' . $_GET['products_id'] . '&reviews_id=' . $_GET['reviews_id'], 'NONSSL', false);
        break;
    /**
     * for music filters:
     */
    case $current_page === FILENAME_DEFAULT && !empty($_GET['typefilter']) && (!empty($_GET['music_genre_id']) || !empty($_GET['record_company_id'])):
        unset($exclude_params[array_search('typefilter', $exclude_params)]);
        $canonical_link = zen_href_link($current_page, zen_get_all_get_params($exclude_params), 'NONSSL', false);
        break;
    /**
     * home page
     * this translates index.php?main_page=index to just index.php (or whatever zen_href_link is doing)
     */
    case $this_is_home_page:
        $canonical_link = preg_replace('/(index.php)(\?)(main_page=)(' . FILENAME_DEFAULT . ')$/', '', (string) zen_href_link(FILENAME_DEFAULT, '', 'NONSSL', false));
        break;
    /**
     * All others
     * uncomment the $canonicalLink = ''; line if you want no special handling for other pages
     */
    default:
        $canonical_link = zen_href_link($current_page, zen_get_all_get_params($exclude_params), 'NONSSL', false);
        //$canonicalLink = '';
        $zco_notifier->notify('NOTIFY_INIT_CANONICAL_DEFAULT', $current_page, $exclude_params, $canonical_link);
        break;
}
$zco_notifier->notify('NOTIFY_INIT_CANONICAL_FINAL', $current_page, $exclude_params, $canonical_link);
unset($exclude_params, $include_c_path, $rogues);