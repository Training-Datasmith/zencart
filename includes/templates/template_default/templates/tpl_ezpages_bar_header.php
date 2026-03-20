<?php

/**
 * Page Template
 *
 * Displays EZ-Pages Header-Bar content.
 *
 * @copyright Copyright 2003-2022 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2020 Dec 25 Modified in v1.5.8-alpha $
 */
/**
 * require code to show EZ-Pages list
 */
include DIR_WS_MODULES . zen_get_module_directory('ezpages_bar_header.php');
if (!empty($var_links_list)) {
    ?>
<div id="navEZPagesTop">
<?php 
    for ($i = 1, $n = sizeof($var_links_list); $i <= $n; $i++) {
        ?>
  <a href="<?php 
        echo $var_links_list[$i]['link'];
        ?>"><?php 
        echo $var_links_list[$i]['name'];
        ?></a><?php 
        echo ($i < $n ? EZPAGES_SEPARATOR_HEADER : '') . "\n";
    }
    // end FOR loop
    ?>
</div>
<?php 
}