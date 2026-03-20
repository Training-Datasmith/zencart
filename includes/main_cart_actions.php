<?php

declare (strict_types=1);
/**
 * Main Shopping Cart actions supported.
 *
 * The main cart actions supported by the shopping_cart class.
 * This can be added to externally using the extra_cart_actions directory.
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Oct 29 Modified in v2.2.0 $
 * @since ZC v1.3.0
 *
 * @var shoppingCart $_SESSION['cart']
 */
use Zencart\File_System\File_System;
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}
/**
 * NOTE: the $goto and $parameters variables are set by init_cart_handler.php
 */
/**
 * Load all PHP files present in the extra_cart_actions subdirectory.
 */
$base_dir = DIR_FS_CATALOG . DIR_WS_INCLUDES . 'extra_cart_actions/';
$mca_filesystem = new File_System();
$files = $mca_filesystem->list_files_from_directory_alpha_sorted($base_dir);
foreach ($files as $file) {
    require $base_dir . $file;
}
/**
 * Load all PHP files present in enabled zc_plugins' extra_cart_actions subdirectories.
 */
foreach ($installed_plugins as $plugin) {
    $plugin_dir = DIR_FS_CATALOG . 'zc_plugins/' . $plugin['unique_key'] . '/' . $plugin['version'] . '/catalog/includes/extra_cart_actions/';
    $files = $mca_filesystem->list_files_from_directory_alpha_sorted($plugin_dir);
    foreach ($files as $file) {
        require $plugin_dir . $file;
    }
}
switch ($_GET['action']) {
    /**
     * customer wants to update the product quantity in their shopping cart
     * delete checkbox or 0 quantity removes from cart
     */
    case 'update_product':
        $_SESSION['cart']->action_update_product($goto, $parameters);
        break;
    /**
     * customer adds a product from the products page
     */
    case 'add_product':
        $_SESSION['cart']->action_add_product($goto, $parameters);
        break;
    case 'buy_now':
        /**
         * performed by the 'buy now' button in product listings and review page
         */
        $_SESSION['cart']->action_buy_now($goto, $parameters);
        break;
    case 'multiple_products_add_product':
        /**
         * performed by the multiple-add-products button
         */
        $_SESSION['cart']->action_multiple_add_product($goto, $parameters);
        break;
    case 'notify':
        $_SESSION['cart']->action_notify($goto, $parameters);
        break;
    case 'notify_remove':
        $_SESSION['cart']->action_notify_remove($goto, $parameters);
        break;
    case 'cust_order':
        $_SESSION['cart']->action_customer_order($goto, $parameters);
        break;
    case 'remove_product':
        $_SESSION['cart']->action_remove_product($goto, $parameters);
        break;
    case 'cart':
        $_SESSION['cart']->action_cart_user_action($goto, $parameters);
        break;
    case 'empty_cart':
        $_SESSION['cart']->reset(true);
        break;
}