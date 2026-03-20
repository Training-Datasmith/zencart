<?php

declare (strict_types=1);
/**
 * Side Box Template
 *
 * @copyright Copyright 2003-2022 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2020 Jul 10 Modified in v1.5.8-alpha $
 */
$content = '';
$content .= '<div id="' . str_replace('_', '-', $box_id . 'Content') . '" class="sideBoxContent">';
$content .= "\n" . '<ul style="margin: 0; padding: 0; list-style-type: none;">' . "\n";
for ($i = 1, $n = sizeof($var_links_list); $i <= $n; $i++) {
    $content .= '<li><a href="' . $var_links_list[$i]['link'] . '">' . $var_links_list[$i]['name'] . '</a></li>' . "\n";
}
// end FOR loop
$content .= '</ul>' . "\n";
$content .= '</div>';