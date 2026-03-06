<?php

declare(strict_types=1);
/**
 * Bootstrap file contains former application_top code
 *
 * Initializes common classes & methods. Controlled by an array which describes
 * the elements to be initialised and the order in which that happens.
 *
 */
require_once('includes/application_bootstrap.php');
/**
 * Prepare init-system
 */

use Zencart\FileSystem\FileSystem;
use Zencart\InitSystem\InitSystem;

if (isset($loaderPrefix)) {
    $loaderPrefix = preg_replace('/[^a-z_]/', '', $loaderPrefix);
} else {
    $loaderPrefix = 'config';
}
$initSystem = new InitSystem('admin', $loaderPrefix, new FileSystem(), $pluginManager, $installedPlugins);

if (defined('DEBUG_AUTOLOAD') && DEBUG_AUTOLOAD == true) {
    $initSystem->setDebug(true);
}

$loaderList = $initSystem->loadAutoLoaders();
$initSystemList = $initSystem->processLoaderList($loaderList);

require(DIR_FS_CATALOG . 'includes/autoload_func.php');
