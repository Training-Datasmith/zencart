<?php

/**
 * Module Template
 *
 * @copyright Copyright 2003-2022 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2020 Dec 25 Modified in v1.5.8-alpha $
 */
?>
<div id="productQuantityDiscounts">
<?php 
if ($zc_hidden_discounts_on) {
    ?>
  <table id="quantityDiscountsDetails">
    <tr>
      <td colspan="1" class="alignCenter">
      <?php 
    echo TEXT_HEADER_DISCOUNTS_OFF;
    ?>
      </td>
    </tr>
    <tr>
      <td colspan="1" class="alignCenter">
      <?php 
    echo $zc_hidden_discounts_text;
    ?>
      </td>
    </tr>
  </table>
<?php 
} else {
    ?>
  <table id="quantityDiscountsDetails">
    <tr>
      <td colspan="<?php 
    echo $column_count + 1;
    ?>" class="alignCenter">
<?php 
    switch ($products_discount_type) {
        case '1':
            echo TEXT_HEADER_DISCOUNT_PRICES_PERCENTAGE;
            break;
        case '2':
            echo TEXT_HEADER_DISCOUNT_PRICES_ACTUAL_PRICE;
            break;
        case '3':
            echo TEXT_HEADER_DISCOUNT_PRICES_AMOUNT_OFF;
            break;
    }
    ?>
      </td>
    </tr>

    <tr>
      <td class="alignCenter"><?php 
    echo $show_qty . '<br>' . $currencies->display_price($show_price, zen_get_tax_rate($products_tax_class_id));
    ?></td>

<?php 
    foreach ($quantity_discounts as $key => $quantity_discount) {
        ?>
<td class="alignCenter"><?php 
        echo $quantity_discount['show_qty'] . '<br>' . $currencies->display_price($quantity_discount['discounted_price'], zen_get_tax_rate($products_tax_class_id));
        ?></td>
<?php 
        $disc_cnt++;
        if ($discount_col_cnt == $disc_cnt && !($key == sizeof($quantity_discount))) {
            $disc_cnt = 0;
            ?>
  </tr><tr>
<?php 
        }
    }
    if ($disc_cnt < $column_count) {
        ?>
    <td class="alignCenter" colspan="<?php 
        echo $column_count + 1 - $disc_cnt + 1;
        ?>"> &nbsp; </td>
<?php 
    }
    ?>
    </tr>
<?php 
    if (zen_has_product_attributes($products_id_current)) {
        ?>
    <tr>
      <td colspan="<?php 
        echo $column_count + 1;
        ?>" class="alignCenter">
        <?php 
        echo TEXT_FOOTER_DISCOUNT_QUANTITIES;
        ?>
      </td>
    </tr>
<?php 
    }
    ?>
  </table>
<?php 
}
// hide discounts
?>
</div>
