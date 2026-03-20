<?php

declare (strict_types=1);
/**
 * read the configuration settings from the db
 * see  {@link  https://docs.zen-cart.com/dev/code/init_system/} for more details.
 * @copyright Copyright 2003-2022 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Scott C Wilson 2020 Aug 01 Modified in v1.5.8-alpha $
 */
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}
use Zencart\Db_Repositories\Configuration_Repository;
use Zencart\Db_Repositories\Product_Type_Layout_Repository;
// need to enable caching in eloquent. for now, no caching @todo
$use_cache = isset($_GET['nocache']) ? false : true;
global $db;
$configuration_repository = new Configuration_Repository($db);
$configuration_repository->load_config_settings();
$product_type_layout_repository = new Product_Type_Layout_Repository($db);
$product_type_layout_repository->load_config_settings();
if (file_exists(DIR_WS_CLASSES . 'db/' . DB_TYPE . '/define_queries.php')) {
    /**
     * Load the database dependant query defines
     */
    include DIR_WS_CLASSES . 'db/' . DB_TYPE . '/define_queries.php';
}