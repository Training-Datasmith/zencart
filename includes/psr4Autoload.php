<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license https://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Oct 03 Modified in v2.2.0 $
 */
/** @var \Aura\Autoload\Loader $psr4Autoloader */
$psr4Autoloader->add_prefix('Zencart\QueryBuilder', DIR_FS_CATALOG . DIR_WS_CLASSES);
$psr4Autoloader->add_prefix('Zencart\Traits', DIR_FS_CATALOG . DIR_WS_CLASSES . 'traits');
$psr4Autoloader->add_prefix('Zencart\FileSystem', DIR_FS_CATALOG . DIR_WS_CLASSES);
$psr4Autoloader->add_prefix('Zencart\InitSystem', DIR_FS_CATALOG . DIR_WS_CLASSES);
$psr4Autoloader->add_prefix('Zencart\PluginManager', DIR_FS_CATALOG . DIR_WS_CLASSES);
$psr4Autoloader->add_prefix('Zencart\LanguageLoader', DIR_FS_CATALOG . DIR_WS_CLASSES . 'ResourceLoaders');
$psr4Autoloader->add_prefix('Zencart\ResourceLoaders', DIR_FS_CATALOG . DIR_WS_CLASSES . 'ResourceLoaders');
$psr4Autoloader->add_prefix('Zencart\PageLoader', DIR_FS_CATALOG . DIR_WS_CLASSES . 'ResourceLoaders');
$psr4Autoloader->add_prefix('Zencart\Events', DIR_FS_CATALOG . DIR_WS_CLASSES);
$psr4Autoloader->add_prefix('Zencart\DbRepositories', DIR_FS_CATALOG . DIR_WS_CLASSES . 'DbRepositories');
// The two App\Models classes are aliases for the above Zencart\DbRepositories classes, so that encap plugins built for prior versions can still type-hint against \App\Models without throwing errors.
$psr4Autoloader->set_class_file('App\Models\PluginControl', DIR_FS_CATALOG . DIR_WS_CLASSES . 'DbRepositories/PluginControl.php');
$psr4Autoloader->set_class_file('App\Models\PluginControlVersion', DIR_FS_CATALOG . DIR_WS_CLASSES . 'DbRepositories/PluginControlVersion.php');
$psr4Autoloader->add_prefix('Zencart\PluginSupport', DIR_FS_CATALOG . DIR_WS_CLASSES . 'PluginSupport');
$psr4Autoloader->add_prefix('Zencart\ViewBuilders', DIR_FS_CATALOG . DIR_WS_CLASSES . 'ViewBuilders');
$psr4Autoloader->add_prefix('Zencart\Exceptions', DIR_FS_CATALOG . DIR_WS_CLASSES . 'Exceptions');
$psr4Autoloader->add_prefix('Zencart\Filters', DIR_FS_CATALOG . DIR_WS_CLASSES . 'Filters');
$psr4Autoloader->add_prefix('Zencart\Request', DIR_FS_CATALOG . DIR_WS_CLASSES);
// -----
// Admin-only classes
//
if (defined('DIR_FS_ADMIN')) {
    $psr4Autoloader->add_prefix('Zencart\Paginator', DIR_FS_ADMIN . DIR_WS_CLASSES);
    $psr4Autoloader->set_class_file('box', DIR_FS_ADMIN . DIR_WS_CLASSES . 'box.php');
    $psr4Autoloader->set_class_file('boxTableBlock', DIR_FS_ADMIN . DIR_WS_CLASSES . 'table_block.php');
    $psr4Autoloader->set_class_file('configurationValidation', DIR_FS_ADMIN . DIR_WS_CLASSES . 'configurationValidation.php');
    $psr4Autoloader->set_class_file('messageStack', DIR_FS_ADMIN . DIR_WS_CLASSES . 'message_stack.php');
    $psr4Autoloader->set_class_file('objectInfo', DIR_FS_ADMIN . DIR_WS_CLASSES . 'object_info.php');
    $psr4Autoloader->set_class_file('products', DIR_FS_CATALOG . DIR_WS_CLASSES . 'products.php');
    //- Deprecated v2.1.0
    $psr4Autoloader->set_class_file('VersionServer', DIR_FS_ADMIN . DIR_WS_CLASSES . 'VersionServer.php');
    $psr4Autoloader->set_class_file('WhosOnline', DIR_FS_ADMIN . DIR_WS_CLASSES . 'WhosOnline.php');
    // -----
    // Storefront-only classes
    //
} else {
    $psr4Autoloader->set_class_file('breadcrumb', DIR_FS_CATALOG . DIR_WS_CLASSES . 'breadcrumb.php');
    $psr4Autoloader->set_class_file('messageStack', DIR_FS_CATALOG . DIR_WS_CLASSES . 'message_stack.php');
    $psr4Autoloader->set_class_file('navigationHistory', DIR_FS_CATALOG . DIR_WS_CLASSES . 'navigation_history.php');
    $psr4Autoloader->set_class_file('template_func', DIR_FS_CATALOG . DIR_WS_CLASSES . 'template_func.php');
    $psr4Autoloader->set_class_file(\Zencart\Search\Search::class, DIR_FS_CATALOG . DIR_WS_CLASSES . 'class.search.php');
    $psr4Autoloader->set_class_file(\Zencart\Search\Search_Options::class, DIR_FS_CATALOG . DIR_WS_CLASSES . 'class.search.php');
}
// -----
// Common admin/storefront classes
//
$psr4Autoloader->set_class_file('category_tree', DIR_FS_CATALOG . DIR_WS_CLASSES . 'category_tree.php');
$psr4Autoloader->set_class_file('Coupon', DIR_FS_CATALOG . DIR_WS_CLASSES . 'Coupon.php');
$psr4Autoloader->set_class_file('CouponValidation', DIR_FS_CATALOG . DIR_WS_CLASSES . 'CouponValidation.php');
$psr4Autoloader->set_class_file('currencies', DIR_FS_CATALOG . DIR_WS_CLASSES . 'currencies.php');
$psr4Autoloader->set_class_file('Customer', DIR_FS_CATALOG . DIR_WS_CLASSES . 'Customer.php');
$psr4Autoloader->set_class_file('language', DIR_FS_CATALOG . DIR_WS_CLASSES . 'language.php');
$psr4Autoloader->set_class_file('MeasurementUnits', DIR_FS_CATALOG . DIR_WS_CLASSES . 'MeasurementUnits.php');
$psr4Autoloader->set_class_file('notifier', DIR_FS_CATALOG . DIR_WS_CLASSES . 'class.notifier.php');
$psr4Autoloader->set_class_file('Product', DIR_FS_CATALOG . DIR_WS_CLASSES . 'Product.php');
$psr4Autoloader->set_class_file('Settings', DIR_FS_CATALOG . DIR_WS_CLASSES . 'Settings.php');
$psr4Autoloader->set_class_file('shoppingCart', DIR_FS_CATALOG . DIR_WS_CLASSES . 'shopping_cart.php');
$psr4Autoloader->set_class_file('sniffer', DIR_FS_CATALOG . DIR_WS_CLASSES . 'sniffer.php');
$psr4Autoloader->set_class_file('TemplateSettings', DIR_FS_CATALOG . DIR_WS_CLASSES . 'TemplateSettings.php');
$psr4Autoloader->set_class_file('upload', DIR_FS_CATALOG . DIR_WS_CLASSES . 'upload.php');
$psr4Autoloader->set_class_file('zcDate', DIR_FS_CATALOG . DIR_WS_CLASSES . 'zcDate.php');
$psr4Autoloader->set_class_file('zcPassword', DIR_FS_CATALOG . DIR_WS_CLASSES . 'class.zcPassword.php');
$psr4Autoloader->set_class_file('ZenShipping', DIR_FS_CATALOG . DIR_WS_CLASSES . 'ZenShipping.php');
$psr4Autoloader->set_class_file(\Zencart\Session_Handler::class, DIR_FS_CATALOG . DIR_WS_CLASSES . 'SessionHandler.php');
$psr4Autoloader->set_class_file('Category', DIR_FS_CATALOG . DIR_WS_CLASSES . 'Category.php');