<?php

declare (strict_types=1);
/**
 * autoloader array for catalog application_top.php
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: lat9 2025 Jun 30 Modified in v2.2.0 $
 */
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}
if (!defined('USE_PCONNECT')) {
    define('USE_PCONNECT', 'false');
}
/**
 *
 * require DIR_WS_INCLUDES . 'version.php';
 * require DIR_WS_CLASSES . 'class.base.php';
 * require DIR_WS_CLASSES . 'class.notifier.php';
 * $zco_notifier = new notifier()'
 * require DIR_WS_CLASSES . 'class.phpmailer.php';
 * require DIR_WS_CLASSES . 'category_tree.php';
 * require DIR_WS_CLASSES . 'cache.php';
 * require DIR_WS_CLASSES . 'sniffer.php';
 * require DIR_WS_CLASSES . 'shopping_cart.php';
 * require DIR_WS_CLASSES . 'navigation_history.php';
 * require DIR_WS_CLASSES . 'currencies.php';
 * require DIR_WS_CLASSES . 'message_stack.php';
 * require DIR_WS_CLASSES . 'template_func.php';
 * require DIR_WS_CLASSES . 'breadcrumb.php';
 * require DIR_WS_CLASSES . 'zcDate.php';
 *
 */
$auto_load_config[0][] = ['autoType' => 'include', 'loadFile' => DIR_WS_INCLUDES . 'version.php'];
//- notifier class loaded via psr4Autoload.php
$auto_load_config[0][] = ['autoType' => 'classInstantiate', 'className' => 'notifier', 'objectName' => 'zco_notifier'];
$auto_load_config[0][] = ['autoType' => 'class', 'loadFile' => 'class.phpmailer.php'];
//- zcPassword class loaded via psr4Autoload.php
$auto_load_config[0][] = ['autoType' => 'classInstantiate', 'className' => 'zcPassword', 'objectName' => 'zcPassword'];
/**
 * Breakpoint 5.
 *
 * $zcDate = new zcDate(); ... will be re-initialized when/if the require_languages.php module is run.
 *
 */
//- zcDate class loaded via psr4Autoload.php
$auto_load_config[5][] = ['autoType' => 'classInstantiate', 'className' => 'zcDate', 'objectName' => 'zcDate'];
/**
 * Breakpoint 30.
 *
 * $zc_cache = new cache();
 *
 */
$auto_load_config[30][] = ['autoType' => 'classInstantiate', 'className' => 'cache', 'objectName' => 'zc_cache'];
/**
 * Breakpoint 40.
 *
 * require 'includes/init_includes/init_db_config_read.php';
 *
 */
$auto_load_config[40][] = ['autoType' => 'init_script', 'loadFile' => 'init_db_config_read.php'];
/**
 * Breakpoint 45.
 *
 * require 'includes/init_includes/init_non_db_settings.php';
 *
 */
$auto_load_config[45][] = ['autoType' => 'init_script', 'loadFile' => 'init_non_db_settings.php'];
/**
 * Breakpoint 50.
 *
 * $sniffer = new sniffer();
 * require 'includes/init_includes/init_gzip.php';
 * require 'includes/init_includes/init_sefu.php';
 */
//- sniffer class loaded via psr4Autoload.php
$auto_load_config[50][] = ['autoType' => 'classInstantiate', 'className' => 'sniffer', 'objectName' => 'sniffer'];
$auto_load_config[50][] = ['autoType' => 'init_script', 'loadFile' => 'init_gzip.php'];
$auto_load_config[50][] = ['autoType' => 'init_script', 'loadFile' => 'init_sefu.php'];
/**
 * Breakpoint 55.
 *
 * require 'includes/init_includes/init_common_elements.php';
 */
$auto_load_config[55][] = ['autoType' => 'init_script', 'loadFile' => 'init_common_elements.php'];
/**
 * Breakpoint 60.
 *
 * require 'includes/init_includes/init_general_funcs.php';
 * require 'includes/init_includes/init_tlds.php';
 *
 */
$auto_load_config[60][] = ['autoType' => 'require', 'loadFile' => DIR_WS_FUNCTIONS . 'functions_osh_update.php'];
$auto_load_config[60][] = ['autoType' => 'init_script', 'loadFile' => 'init_general_funcs.php'];
$auto_load_config[60][] = ['autoType' => 'init_script', 'loadFile' => 'init_tlds.php'];
/**
 * Breakpoint 70.
 *
 * require 'includes/init_includes/init_sessions.php';
 *
 */
$auto_load_config[70][] = ['autoType' => 'init_script', 'loadFile' => 'init_sessions.php'];
/**
 * Breakpoint 75.
 *
 * require 'includes/init_includes/init_languages.php';
 *
 */
$auto_load_config[75][] = ['autoType' => 'init_script', 'loadFile' => 'init_languages.php'];
/**
 * Breakpoint 80.
 *
 * if (!$_SESSION['cart']) $_SESSION['cart'] = new shoppingCart();
 *
 */
//- shoppingCart class loaded via psr4Autoload.php
$auto_load_config[80][] = ['autoType' => 'classInstantiate', 'className' => 'shoppingCart', 'objectName' => 'cart', 'checkInstantiated' => true, 'classSession' => true];
//- Zencart\Search\Search loaded via psr4Autoload.php
$auto_load_config[80][] = ['autoType' => 'classInstantiate', 'className' => \Zencart\Search\Search::class, 'objectName' => 'search'];
/**
 * Breakpoint 90.
 *
 * currencies = new currencies();
 *
 */
//- currencies class loaded via psr4Autoload.php
$auto_load_config[90][] = ['autoType' => 'classInstantiate', 'className' => 'currencies', 'objectName' => 'currencies'];
/**
 * Breakpoint 96.
 *
 * require 'includes/init_includes/init_sanitize.php';
 *
 */
$auto_load_config[96][] = ['autoType' => 'init_script', 'loadFile' => 'init_sanitize.php'];
/**
 * Breakpoint 100.
 *
 * if (!$_SESSION['navigaton']) $_SESSION['navigation'] = new navigationHistory();
 * $template = new template_func();
 *
 */
//- template_func class loaded via psr4Autoload.php
$auto_load_config[100][] = ['autoType' => 'classInstantiate', 'className' => 'template_func', 'objectName' => 'template'];
//- navigationHistory class loaded via psr4Autoload.php
$auto_load_config[100][] = ['autoType' => 'classInstantiate', 'className' => 'navigationHistory', 'objectName' => 'navigation', 'checkInstantiated' => true, 'classSession' => true];
/**
 * Breakpoint 110.
 *
 * require 'includes/init_includes/init_templates.php';
 *
 */
$auto_load_config[110][] = ['autoType' => 'init_script', 'loadFile' => 'init_templates.php'];
/**
 * Breakpoint 115
 *
 * require 'includes/init_includes/init_split_page_results.php';
 */
$auto_load_config[115][] = ['autoType' => 'init_script', 'loadFile' => 'init_split_page_results.php'];
/**
 * Breakpoint 120.
 *
 * $_SESSION['navigation']->add_current_page();
 * require 'includes/init_includes/init_currencies.php';
 *
 */
$auto_load_config[120][] = ['autoType' => 'objectMethod', 'objectName' => 'navigation', 'methodName' => 'add_current_page'];
$auto_load_config[120][] = ['autoType' => 'init_script', 'loadFile' => 'init_currencies.php'];
/**
 * Breakpoint 130.
 *
 * messageStack = new messageStack();
 *
 */
//- messageStack class loaded via psr4Autoload.php
$auto_load_config[130][] = ['autoType' => 'classInstantiate', 'className' => 'messageStack', 'objectName' => 'messageStack'];
/**
 * Breakpoint 135.
 *
 * require 'includes/init_includes/init_customer_auth.php';
 *
 */
$auto_load_config[135][] = ['autoType' => 'init_script', 'loadFile' => 'init_customer_auth.php'];
/**
 * Breakpoint 138.
 *
 * require('includes/init_includes/init_coupons.php');
 *
 */
$auto_load_config[138][] = ['autoType' => 'init_script', 'loadFile' => 'init_coupons.php'];
/**
 * Breakpoint 140.
 *
 * require 'includes/init_includes/init_cart_handler.php';
 *
 */
$auto_load_config[140][] = ['autoType' => 'init_script', 'loadFile' => 'init_cart_handler.php'];
/**
 * Breakpoint 150.
 *
 * require 'includes/init_includes/init_special_funcs.php';
 *
 */
$auto_load_config[150][] = ['autoType' => 'init_script', 'loadFile' => 'init_special_funcs.php'];
/**
 * Breakpoint 160.
 *
 * require 'includes/init_includes/init_category_path.php';
 * $breadcrumb = new breadcrumb();
 */
//- breadcrumb class loaded via psr4Autoloader.php
$auto_load_config[160][] = ['autoType' => 'classInstantiate', 'className' => 'breadcrumb', 'objectName' => 'breadcrumb'];
$auto_load_config[160][] = ['autoType' => 'init_script', 'loadFile' => 'init_category_path.php'];
/**
 * Breakpoint 170.
 *
 * require 'includes/init_includes/init_add_crumbs.php';
 *
 */
$auto_load_config[170][] = ['autoType' => 'init_script', 'loadFile' => 'init_add_crumbs.php'];
/**
 * Breakpoint 175.
 *
 * require 'includes/init_includes/init_observers.php';
 *
 */
$auto_load_config[175][] = ['autoType' => 'init_script', 'loadFile' => 'init_observers.php'];
/**
 * Breakpoint 180.
 *
 * require 'includes/init_includes/init_header.php';
 *
 */
$auto_load_config[180][] = ['autoType' => 'init_script', 'loadFile' => 'init_header.php'];
/**
 * NOTE: Most plugins should be added from point 200 onward.
 */