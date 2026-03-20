<?php

declare (strict_types=1);
/**
 * column_right module
 *
 * @copyright Copyright 2003-2024 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: highburyeye 2023 Sep 25 Modified in v2.0.0-alpha1 $
 */
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}
use Zencart\Db_Repositories\Layout_Box_Repository;
use Zencart\File_System\File_System;
use Zencart\Resource_Loaders\Sidebox_Finder;
$column_box_default = 'tpl_box_default_right.php';
// Check if there are boxes for the column
global $db;
$layout_box_repository = new Layout_Box_Repository($db);
$sideboxes = $layout_box_repository->get_active_for_location(1, $template_dir, 100);
$column_width = (int) BOX_WIDTH_RIGHT;
foreach ($sideboxes as $sidebox) {
    $box_file = (new Sidebox_Finder(new File_System()))->sidebox_path($sidebox, $template_dir, true);
    if ($box_file !== false) {
        $box_id = zen_get_box_id($sidebox['layout_box_name']);
        include $box_file . $sidebox['layout_box_name'];
    }
}
$box_id = '';