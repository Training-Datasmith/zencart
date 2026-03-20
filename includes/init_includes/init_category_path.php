<?php

declare (strict_types=1);
/**
 * pre-calculate the category path
 * see  {@link  https://docs.zen-cart.com/dev/code/init_system/} for more details.
 * @copyright Copyright 2003-2023 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Scott C Wilson 2022 Nov 03 Modified in v1.5.8a $
 */
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}
$show_welcome = false;
if (isset($_GET['cPath'])) {
    $c_path = $_GET['cPath'];
} elseif (isset($_GET['products_id']) && !zen_check_url_get_terms()) {
    $c_path = zen_get_product_path($_GET['products_id']);
} else if ($current_page == 'index' && SHOW_CATEGORIES_ALWAYS == '1' && !zen_check_url_get_terms()) {
    $show_welcome = true;
    $c_path = defined('CATEGORIES_START_MAIN') ? CATEGORIES_START_MAIN : '';
} else {
    $show_welcome = false;
    $c_path = '';
}
if (zen_not_null($c_path)) {
    $c_path_array = zen_parse_category_path($c_path);
    $c_path = implode('_', $c_path_array);
    $current_category_id = $c_path_array[count($c_path_array) - 1];
} else {
    $current_category_id = TOPMOST_CATEGORY_PARENT_ID;
    $c_path_array = [];
}
// determine whether the current page is the home page or a product listing
//$this_is_home_page = ($current_page=='index' && ((int)$cPath == 0 || $show_welcome == true));
$this_is_home_page = $current_page == 'index' && (!isset($_GET['cPath']) || $_GET['cPath'] == '') && (!isset($_GET['manufacturers_id']) || $_GET['manufacturers_id'] == '') && (!isset($_GET['typefilter']) || $_GET['typefilter'] == '');