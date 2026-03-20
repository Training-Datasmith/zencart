<?php

declare (strict_types=1);
/**
 * auto-load and instantiate all /includes/classes/observers/auto.xxxxxxxxx.php classes
 *
 * This looks for any files in the DIR_WS_CLASSES/observers folder matching the naming convention of "auto.XXXXXX.php"
 * It then automatically "include"s those files.
 * And then it checks to see whether the XXXXXXXXX part of the filename matches a class name using "zcObserver" + the CamelCased XXXXXXXXX string.
 * ie: zcObserverTemplateFrameworkAbc would match auto.template_framework_abc.php
 * If the properly named class exists, then it instantiates that class using an object of the same name.  If the class inside the file is NOT properly named, it will NOT be instantiated, despite being loaded.
 *
 * The assumption is that the class is an observer class which properly extends the base class (or implements NotifierManager and ObserverManager)
 * All normal observer class behavior applies.
 *
 * This fires at AutoLoader point 175, so all previously-processed system dependencies are in place.
 * If you need an observer class to fire at a much earlier point so it fires before other system processes, you'll need to add your own auto_loaders/config.yyyyy.php file with relevant rules to load those observers.
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 19 Modified in v2.2.0 $
 */
use Zencart\File_System\File_System;
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}
$observers_main = (new File_System())->list_files_from_directory(DIR_WS_CLASSES . 'observers/', '~(^(auto\.|Auto[A-Z]).*\.php$)~');
$observers_main = array_map(static fn($item): string => DIR_WS_CLASSES . 'observers/' . $item, $observers_main);
$context = IS_ADMIN_FLAG ? 'admin' : 'catalog';
$observers_plugins = [];
foreach ($installed_plugins as $plugin) {
    $path = DIR_FS_CATALOG . 'zc_plugins/' . $plugin['unique_key'] . '/' . $plugin['version'] . '/' . $context . '/' . DIR_WS_CLASSES . 'observers/';
    $observers_plugin = (new File_System())->list_files_from_directory($path, '~(^(auto\.|Auto[A-Z]).*\.php$)~');
    $observers_plugin = array_map(static fn($item): string => $path . $item, $observers_plugin);
    $observers_plugins = array_merge($observers_plugins, $observers_plugin);
}
$observers = array_merge($observers_plugins, $observers_main);
// sort by filename so that observers are loaded in a predictable order
$basenames = array_map(basename(...), $observers);
array_multisort($basenames, SORT_ASC, SORT_NATURAL | SORT_FLAG_CASE, $observers, SORT_ASC, SORT_NATURAL | SORT_FLAG_CASE);
unset($basenames);
// instantiate discovered observer classes
foreach ($observers as $observer) {
    if (!file_exists($observer)) {
        continue;
    }
    include $observer;
    $observer_filename = basename($observer);
    $class_name = preg_replace('~(^auto\.|\.php$)~', '', $observer_filename);
    $psr4class_name = base::camelize($class_name, true);
    $object_name = 'zcObserver' . base::camelize($class_name, true);
    if (class_exists($object_name)) {
        // 'auto.' prefix in filename and 'zcObserver' prefix in class name
        ${$object_name} = new $object_name();
    } elseif (class_exists($psr4class_name)) {
        // 'Auto' prefix in filename and class name matches filename
        ${$object_name} = new $psr4class_name();
    } elseif (class_exists($alternate_class_name = preg_replace('~^Auto~', '', $psr4class_name))) {
        // 'Auto' prefix in filename but not in class name
        ${$object_name} = new $alternate_class_name();
    } else {
        error_log(sprintf('ERROR: Observer class %s (or alternate class %s) could not be instantiated despite file %s being found. Please follow the correct naming convention for the class name inside the file.', $psr4class_name, $object_name, $observer));
    }
}