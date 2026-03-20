<?php

declare (strict_types=1);
/**
 * File contains the autoloader loop
 *
 * The autoloader loop takes the array from the auto_loaders directory
 * and uses it to construct the InitSystem.
 * see  {@link  https://docs.zen-cart.com/dev/code/init_system/} for more details.
 * @copyright Copyright 2003-2022 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Scott C Wilson 2022 Jul 05 Modified in v1.5.8-alpha $
 */
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}
$debug_autoload = false;
if (defined('DEBUG_AUTOLOAD') && DEBUG_AUTOLOAD == true) {
    $debug_autoload = true;
}
if ($debug_autoload) {
    echo '<pre>$initSystemList=<br>';
    print_r($init_system_list);
    echo '</pre>';
}
foreach ($init_system_list as $entry) {
    switch ($entry['type']) {
        case 'include':
            if ($entry['forceLoad']) {
                if ($debug_autoload) {
                    echo 'case "include": ' . $entry['filePath'] . "<br>\n";
                }
                include $entry['filePath'];
            } else {
                if ($debug_autoload) {
                    echo 'case "include_once": ' . $entry['filePath'] . "<br>\n";
                }
                include_once $entry['filePath'];
            }
            break;
        case 'require':
            if ($entry['forceLoad']) {
                if ($debug_autoload) {
                    echo 'case "require": ' . $entry['filePath'] . "<br>\n";
                }
                require $entry['filePath'];
            } else {
                if ($debug_autoload) {
                    echo 'case "require_once": ' . $entry['filePath'] . "<br>\n";
                }
                require_once $entry['filePath'];
            }
            break;
        case 'class':
            if ($debug_autoload) {
                echo 'case "class": ' . $entry['class'] . "<br>\n";
            }
            $object_name = $entry['object'];
            $class_name = $entry['class'];
            ${$object_name} = new $class_name();
            break;
        case 'sessionClass':
            if ($debug_autoload) {
                echo 'case "sessionClass": ' . $entry['class'] . "<br>\n";
            }
            $object_name = $entry['object'];
            $class_name = $entry['class'];
            if (!$entry['checkInstantiated'] || !isset($_SESSION[$object_name])) {
                $_SESSION[$object_name] = new $class_name();
            }
            break;
        case 'objectMethod':
            if ($debug_autoload) {
                echo 'case "objectMethod": ' . '$entry[\'method\']=' . $entry['method'] . ', $entry[\'object\']=' . $entry['object'] . "<br>\n";
            }
            $object_name = $entry['object'];
            $method_name = $entry['method'];
            if (isset($_SESSION[$object_name]) && is_object($_SESSION[$object_name])) {
                $_SESSION[$object_name]->{$method_name}();
            } else {
                ${$object_name}->{$method_name}();
            }
            break;
    }
}