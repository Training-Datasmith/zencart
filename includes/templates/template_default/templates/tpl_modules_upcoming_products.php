<?php

/**
 * Module Template
 *
 * @copyright Copyright 2003-2024 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: lat9 2024 Apr 25 Modified in v2.0.1 $
 */
?>
<!-- bof: upcoming_products -->
<fieldset id="upcoming-products" class="clearBoth">
<legend><?php 
echo TABLE_HEADING_UPCOMING_PRODUCTS;
?></legend>
<table id="upcomingProductsTable">
<caption><?php 
echo CAPTION_UPCOMING_PRODUCTS;
?></caption>
  <tr>
    <th scope="col" id="upProductsHeading"><?php 
echo TABLE_HEADING_PRODUCTS;
?></th>
    <th scope="col" id="upDateHeading"><?php 
echo TABLE_HEADING_DATE_EXPECTED;
?></th>
  </tr>
<?php 
for ($i = 0, $row = 0, $n = sizeof($expected_items); $i < $n; $i++, $row++) {
    $row_class = $row / 2 == floor($row / 2) ? 'rowEven' : 'rowOdd';
    echo '  <tr class="' . $row_class . '">' . "\n";
    echo '    <td><a href="' . zen_href_link(zen_get_info_page($expected_items[$i]['products_id']), 'cPath=' . $products_in_category[$expected_items[$i]['products_id']] . '&products_id=' . $expected_items[$i]['products_id']) . '">' . $expected_items[$i]['products_name'] . '</a></td>' . "\n";
    echo '    <td class="alignRight">' . zen_date_short($expected_items[$i]['date_expected']) . '</td>' . "\n";
    echo '  </tr>' . "\n";
}
?>
</table>
</fieldset>
<!-- eof: upcoming_products -->
