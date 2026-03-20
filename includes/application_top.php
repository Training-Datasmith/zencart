<?php

declare (strict_types=1);
/**
 * application_top.php Common actions carried out at the start of each page invocation.
 *
 * Initializes common classes & methods. Controlled by an array which describes
 * the elements to be initialised and the order in which that happens.
 * see  {@link  https://docs.zen-cart.com/dev/code/init_system/} for more details.
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Oct 22 Modified in v2.2.0 $
 */
use Zencart\Db_Repositories\Plugin_Control_Repository;
use Zencart\Db_Repositories\Plugin_Control_Version_Repository;
use Zencart\File_System\File_System;
use Zencart\Init_System\Init_System;
use Zencart\Plugin_Manager\Plugin_Manager;
// Set session ID
$zen_session_id = 'zenid';
/**
 * inoculate against hack attempts which waste CPU cycles
 */
$contaminated = isset($_FILES['GLOBALS']) || isset($_REQUEST['GLOBALS']);
$params_to_avoid = ['GLOBALS', '_COOKIE', '_ENV', '_FILES', '_GET', '_POST', '_REQUEST', '_SERVER', '_SESSION', 'HTTP_COOKIE_VARS', 'HTTP_ENV_VARS', 'HTTP_GET_VARS', 'HTTP_POST_VARS', 'HTTP_POST_FILES', 'HTTP_RAW_POST_DATA', 'HTTP_SERVER_VARS', 'HTTP_SESSION_VARS', 'autoLoadConfig', 'mosConfig_absolute_path', 'function', 'hash', 'main', 'vars'];
foreach ($params_to_avoid as $key) {
    if (isset($_GET[$key]) || isset($_POST[$key]) || isset($_COOKIE[$key])) {
        $contaminated = true;
        break;
    }
}
$params_to_check = [$zen_session_id, 'main_page', 'cPath', 'products_id', 'language', 'currency', 'action', 'manufacturers_id', 'pID', 'pid', 'reviews_id', 'filter_id', 'sort', 'number_of_uploads', 'notify', 'page_holder', 'chapter', 'alpha_filter_id', 'typefilter', 'disp_order', 'id', 'key', 'music_genre_id', 'record_company_id', 'set_session_login', 'faq_item', 'edit', 'delete', 'search_in_description', 'dfrom', 'pfrom', 'dto', 'pto', 'inc_subcat', 'payment_error', 'order', 'gv_no', 'pos', 'addr', 'error', 'count', 'error_message', 'info_message', 'cID', 'page', 'credit_class_error_code'];
if (!$contaminated) {
    foreach ($params_to_check as $key) {
        if (!isset($_GET[$key])) {
            continue;
        }
        if (is_array($_GET[$key])) {
            $contaminated = true;
            break;
        }
        if (str_starts_with(strtolower((string) $_GET[$key]), 'http') || str_contains((string) $_GET[$key], '//')) {
            $contaminated = true;
            break;
        }
        $len = in_array($key, [$zen_session_id, 'error_message', 'payment_error']) ? 255 : 43;
        if (strlen((string) $_GET[$key]) > $len) {
            $contaminated = true;
            break;
        }
    }
}
/**
 * reject suspicious non-ASCII characters
 * allows standard printable ASCII but flags common exploit symbols
 */
if (!empty($_SERVER['QUERY_STRING'])) {
    // check for the specific '¤' (%C2%A4) or characters outside standard range
    // allow basic printable ASCII but specifically target high-bit "junk"
    if (preg_match('/[\x00-\x1F\x7F-\xFF]/', (string) $_SERVER['QUERY_STRING'])) {
        $contaminated = true;
    }
    // cap query string length (prevents buffer overflow/fuzzing)
    if (strlen((string) $_SERVER['QUERY_STRING']) > 256) {
        $contaminated = true;
    }
}
/**
 * reject parameter pollution (any repeated keys)
 * scans the raw query string for any key appearing more than twice.
 */
if (!empty($_SERVER['QUERY_STRING'])) {
    // break the query string into individual "key=value" pairs
    $pairs = explode('&', (string) $_SERVER['QUERY_STRING']);
    $keys = [];
    foreach ($pairs as $pair) {
        // get just the part before the "="
        $parts = explode('=', $pair, 2);
        // skip if the pair is empty (e.g., &&) or the key is missing
        if (empty($parts[0])) {
            continue;
        }
        $key = strtolower($parts[0]);
        $keys[] = $key;
    }
    // count occurrences of each key
    $counts = array_count_values($keys);
    foreach ($counts as $count) {
        // allow one duplication (possibly accidental), more than 2 is not accidental
        if ($count > 2) {
            $contaminated = true;
            break;
        }
    }
}
/**
 * reject crawler 'BUY NOW' attempts
 * crawlers should never be adding items to the cart.
 */
if (!$contaminated && isset($_GET['action']) && $_GET['action'] === 'buy_now') {
    $is_crawler_ua = empty($_SERVER['HTTP_USER_AGENT']) || preg_match('/bot|crawl|spider|facebook|meta|externalagent/i', (string) $_SERVER['HTTP_USER_AGENT']);
    $has_internal_referer = !empty($_SERVER['HTTP_REFERER']) && parse_url((string) $_SERVER['HTTP_REFERER'], PHP_URL_HOST) === $_SERVER['HTTP_HOST'];
    if ($is_crawler_ua || !$has_internal_referer) {
        $contaminated = true;
    }
}
unset($params_to_check, $params_to_avoid, $key);
if ($contaminated) {
    header('HTTP/1.1 406 Not Acceptable');
    exit(0);
}
unset($contaminated, $len);
/* *** END OF INOCULATION *** */
// if session id is reconfigured, then we want to exclude its use immediately
if ($zen_session_id !== 'zenid') {
    unset($_GET['zenid'], $_GET['amp;zenid'], $_REQUEST['zenid']);
}
/**
 * boolean used to see if we are in the admin script, obviously set to false here.
 */
define('IS_ADMIN_FLAG', false);
/**
 * integer saves the time at which the script started.
 */
define('PAGE_PARSE_START_TIME', microtime());
@ini_set('arg_separator.output', '&');
@ini_set('html_errors', '0');
/**
 * Set the local configuration parameters - mainly for developers
 */
if (file_exists('includes/local/configure.php')) {
    /**
     * load any local(user created) configure file.
     */
    include 'includes/local/configure.php';
}
/**
 * boolean if true the autoloader scripts will be parsed and their output shown. For debugging purposes only.
 */
define('DEBUG_AUTOLOAD', false);
/**
 * set the level of error reporting
 *
 * Note STRICT_ERROR_REPORTING should never be set to true on a production site.
 * It is mainly there to show php warnings during testing/bug fixing phases.
 */
if (defined('STRICT_ERROR_REPORTING') && STRICT_ERROR_REPORTING == true) {
    @ini_set('display_errors', true);
    error_reporting(defined('STRICT_ERROR_REPORTING_LEVEL') ? STRICT_ERROR_REPORTING_LEVEL : E_ALL);
} else {
    error_reporting(0);
}
date_default_timezone_set(date_default_timezone_get());
/*
 * Check for a valid system locale, and override if invalid or set to 'C' which means 'unconfigured'
 * It will be overridden later via language-selection operations anyway, but a valid default must be set for zcDate class methods to work
 */
$detected_locale = setlocale(LC_TIME, '0');
if ($detected_locale === false || $detected_locale === 'C') {
    setlocale(LC_TIME, ['en_US', 'en_US.UTF-8', 'en-US', 'en']);
}
if (file_exists('./not_for_release/testFramework/Support/application_testing.php')) {
    require './not_for_release/testFramework/Support/application_testing.php';
}
/**
 * check for and include load application parameters
 */
if (!defined('ZENCART_TESTFRAMEWORK_RUNNING')) {
    if (file_exists('includes/configure.php')) {
        /**
         * load the main configure file.
         */
        include 'includes/configure.php';
    } elseif (!defined('DIR_FS_CATALOG') && !defined('HTTP_SERVER') && !defined('DIR_WS_CATALOG') && !defined('DIR_WS_INCLUDES')) {
        $problem_string = 'includes/configure.php not found';
        require 'includes/templates/template_default/templates/tpl_zc_install_suggested_default.php';
        exit;
    }
}
/**
 * if main configure file doesn't contain valid info (ie: is dummy or doesn't match filestructure, display assistance page to suggest running the installer)
 */
if (!defined('DIR_FS_CATALOG') || !is_dir(DIR_FS_CATALOG . '/includes/classes')) {
    $problem_string = 'includes/configure.php file contents invalid.  ie: DIR_FS_CATALOG not valid or not set';
    require 'includes/templates/template_default/templates/tpl_zc_install_suggested_default.php';
    exit;
}
/**
 * check for and load system defined path constants
 */
if (file_exists('includes/defined_paths.php')) {
    /**
     * load the system-defined path constants
     */
    require 'includes/defined_paths.php';
} else {
    die('ERROR: /includes/defined_paths.php file not found. Cannot continue.');
}
require DIR_FS_CATALOG . DIR_WS_FUNCTIONS . 'php_polyfills.php';
require DIR_FS_CATALOG . DIR_WS_FUNCTIONS . 'zen_define_default.php';
/**
 * Register error-handling functions
 */
require DIR_FS_CATALOG . DIR_WS_FUNCTIONS . 'functions_error_handling.php';
zen_enable_error_logging();
/**
 * include the list of extra configure files
 */
foreach (glob(DIR_WS_INCLUDES . 'extra_configures/*.php') ?? [] as $file) {
    include $file;
}
/**
 * determine install status
 */
if (!defined('ZENCART_TESTFRAMEWORK_RUNNING')) {
    if (!file_exists('includes/configure.php') && !file_exists('includes/local/configure.php') || DB_TYPE == '' || !file_exists('includes/classes/db/' . DB_TYPE . '/query_factory.php') || !file_exists('includes/autoload_func.php')) {
        $problem_string = 'includes/configure.php file empty or file not found, OR wrong DB_TYPE set, OR cannot find includes/autoload_func.php which suggests paths are wrong or files were not uploaded correctly';
        require 'includes/templates/template_default/templates/tpl_zc_install_suggested_default.php';
        header('location: zc_install/index.php');
        exit;
    }
}
/**
 * psr-4 autoloading
 */
require DIR_FS_CATALOG . DIR_WS_CLASSES . 'vendors/AuraAutoload/src/Loader.php';
$psr4Autoloader = new \Aura\Autoload\Loader();
$psr4Autoloader->register();
require 'includes/psr4Autoload.php';
require DIR_FS_CATALOG . DIR_WS_CLASSES . 'class.base.php';
require DIR_FS_CATALOG . DIR_WS_CLASSES . 'query_cache.php';
$query_cache = new Query_Cache();
require DIR_FS_CATALOG . DIR_WS_CLASSES . 'cache.php';
$zc_cache = new cache();
require 'includes/init_includes/init_file_db_names.php';
require 'includes/init_includes/init_database.php';
$plugin_manager = new Plugin_Manager(new Plugin_Control_Repository($db), new Plugin_Control_Version_Repository($db));
$installed_plugins = $plugin_manager->get_installed_plugins();
$fs = new File_System();
$fs->load_files_from_plugins_directory($installed_plugins, 'catalog/includes/extra_configures', '~^[^\._].*\.php$~i');
$fs->load_files_from_plugins_directory($installed_plugins, 'catalog/includes/extra_datafiles', '~^[^\._].*\.php$~i');
$fs->load_files_from_plugins_directory($installed_plugins, '', '~^database_tables\.php$~i');
$fs->load_files_from_plugins_directory($installed_plugins, '', '~^filenames\.php$~i');
foreach ($installed_plugins as $plugin) {
    $namespace_admin = 'Zencart\Plugins\Admin\\' . ucfirst((string) $plugin['unique_key']);
    $namespace_catalog = 'Zencart\Plugins\Catalog\\' . ucfirst((string) $plugin['unique_key']);
    $file_path = DIR_FS_CATALOG . 'zc_plugins/' . $plugin['unique_key'] . '/' . $plugin['version'] . '/';
    $file_path_admin = $file_path . 'admin/includes/classes/';
    $file_path_catalog = $file_path . 'catalog/includes/classes/';
    $psr4Autoloader->add_prefix($namespace_admin, $file_path_admin);
    $psr4Autoloader->add_prefix($namespace_catalog, $file_path_catalog);
}
if (isset($loader_prefix)) {
    $loader_prefix = preg_replace('/[^a-z_]/', '', $loader_prefix);
} else {
    $loader_prefix = 'config';
}
$init_system = new Init_System('catalog', $loader_prefix, new File_System(), $plugin_manager, $installed_plugins);
if (defined('DEBUG_AUTOLOAD') && DEBUG_AUTOLOAD == true) {
    $init_system->set_debug(true);
}
$loader_list = $init_system->load_auto_loaders();
$init_system_list = $init_system->process_loader_list($loader_list);
require DIR_FS_CATALOG . 'includes/autoload_func.php';
/**
 * load the counter code
**/
if (empty($spider_flag)) {
    // counter and counter history
    require DIR_WS_INCLUDES . 'counter.php';
}
// get customers unique IP that paypal does not touch
$customers_ip_address = $_SERVER['REMOTE_ADDR'];
if (!isset($_SESSION['customers_ip_address'])) {
    $_SESSION['customers_ip_address'] = $customers_ip_address;
}