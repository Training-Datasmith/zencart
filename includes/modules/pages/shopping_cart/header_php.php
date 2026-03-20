<?php

declare (strict_types=1);
/**
 * shopping_cart header_php.php
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Aug 30 Modified in v2.2.0 $
 */
// This should be first line of the script:
$zco_notifier->notify('NOTIFY_HEADER_START_SHOPPING_CART');
require DIR_WS_MODULES . zen_get_module_directory('require_languages.php');
$breadcrumb->add(NAVBAR_TITLE);
if (isset($_GET['jscript']) && $_GET['jscript'] == 'no') {
    $message_stack->add('shopping_cart', PAYMENT_JAVASCRIPT_DISABLED, 'error');
}
// Validate Cart for checkout
$_SESSION['valid_to_checkout'] = true;
$_SESSION['cart_errors'] = '';
$_SESSION['cart']->get_products(true);
// used to display invalid cart issues when checkout is selected that validated cart and returned to cart due to errors
if (isset($_SESSION['valid_to_checkout']) && $_SESSION['valid_to_checkout'] == false) {
    $message_stack->add('shopping_cart', ERROR_CART_UPDATE . $_SESSION['cart_errors'], 'caution');
}
$shipping_weight = $_SESSION['cart']->show_weight();
$number_of_items_in_cart = $_SESSION['cart']->count_contents();
$cart_total_price = $_SESSION['cart']->show_total();
$prod_img_width = (int) IMAGE_SHOPPING_CART_WIDTH;
$prod_img_height = (int) IMAGE_SHOPPING_CART_HEIGHT;
$flag_any_out_of_stock = false;
$product_array = [];
$products = $_SESSION['cart']->get_products();
$zco_notifier->notify('NOTIFY_HEADER_SHOPPING_CART_BEFORE_PRODUCTS_LOOP', null, $products);
for ($i = 0, $n = count($products); $i < $n; $i++) {
    $flag_stock_check = '';
    $ppe = $ppt = 0;
    $row_class = $i / 2 == floor($i / 2) ? 'rowEven' : 'rowOdd';
    $attribute_hidden_field = '';
    $attr_array = [];
    $products_name = $products[$i]['name'];
    $products_model = $products[$i]['model'];
    // Push all attribute information into an array
    if (isset($products[$i]['attributes']) && is_array($products[$i]['attributes'])) {
        if (PRODUCTS_OPTIONS_SORT_ORDER == '0') {
            $options_order_by = " ORDER BY LPAD(popt.products_options_sort_order,11,'0')";
        } else {
            $options_order_by = ' ORDER BY popt.products_options_name';
        }
        foreach ($products[$i]['attributes'] as $option => $value) {
            $sql = 'SELECT popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pa.attributes_image
                    FROM ' . TABLE_PRODUCTS_OPTIONS . ' popt, ' . TABLE_PRODUCTS_OPTIONS_VALUES . ' poval, ' . TABLE_PRODUCTS_ATTRIBUTES . ' pa
                    WHERE pa.products_id = :productsID
                    AND pa.options_id = :optionsID
                    AND pa.options_id = popt.products_options_id
                    AND pa.options_values_id = :optionsValuesID
                    AND pa.options_values_id = poval.products_options_values_id
                    AND popt.language_id = :languageID
                    AND poval.language_id = :languageID ' . $options_order_by;
            $sql = $db->bind_vars($sql, ':productsID', $products[$i]['id'], 'integer');
            $sql = $db->bind_vars($sql, ':optionsID', $option, 'integer');
            $sql = $db->bind_vars($sql, ':optionsValuesID', $value, 'integer');
            $sql = $db->bind_vars($sql, ':languageID', $_SESSION['languages_id'], 'integer');
            $attributes_values = $db->Execute($sql);
            if ($attributes_values->EOF) {
                continue;
            }
            if ($value == PRODUCTS_OPTIONS_VALUES_TEXT_ID) {
                $attribute_hidden_field .= zen_draw_hidden_field('id[' . $products[$i]['id'] . '][' . TEXT_PREFIX . $option . ']', $products[$i]['attributes_values'][$option]);
                $attr_value = htmlspecialchars((string) $products[$i]['attributes_values'][$option], ENT_COMPAT, CHARSET, true);
            } else {
                $attribute_hidden_field .= zen_draw_hidden_field('id[' . $products[$i]['id'] . '][' . $option . ']', $value);
                $attr_value = $attributes_values->fields['products_options_values_name'];
            }
            $attr_array[$option]['products_options_name'] = $attributes_values->fields['products_options_name'];
            $attr_array[$option]['options_values_id'] = $value;
            $attr_array[$option]['products_options_values_name'] = $attr_value;
            $attr_array[$option]['options_values_price'] = $attributes_values->fields['options_values_price'];
            $attr_array[$option]['price_prefix'] = $attributes_values->fields['price_prefix'];
            $attr_array[$option]['image'] = $attributes_values->fields['attributes_image'];
            $zco_notifier->notify('NOTIFY_HEADER_SHOPPING_CART_IN_ATTRIBUTES_LOOP', $option, $attr_array, $attributes_values->fields, $value, $products, $i);
        }
    }
    //end foreach [attributes]
    // Stock Check
    if (STOCK_CHECK == 'true') {
        $qty_available = zen_get_products_stock($products[$i]['id']);
        // compare against product inventory, and against mixed=YES
        if ($qty_available - $products[$i]['quantity'] < 0 || $qty_available - $_SESSION['cart']->in_cart_mixed($products[$i]['id']) < 0) {
            $flag_stock_check = '<span class="markProductOutOfStock">' . STOCK_MARK_PRODUCT_OUT_OF_STOCK . '</span>';
            $flag_any_out_of_stock = true;
        }
    }
    $link_products_image = zen_href_link(zen_get_info_page($products[$i]['id']), 'products_id=' . $products[$i]['id']);
    $link_products_name = zen_href_link(zen_get_info_page($products[$i]['id']), 'products_id=' . $products[$i]['id']);
    $products_image = IMAGE_SHOPPING_CART_STATUS == 1 ? zen_image(DIR_WS_IMAGES . $products[$i]['image'], $products[$i]['name'], $prod_img_width, $prod_img_height) : '';
    $show_products_quantity_max = zen_get_products_quantity_order_max($products[$i]['id']);
    $show_fixed_quantity = ($show_products_quantity_max == 1 or zen_get_products_qty_box_status($products[$i]['id']) == 0) ? true : false;
    $show_fixed_quantity_amount = $products[$i]['quantity'] . zen_draw_hidden_field('cart_quantity[]', $products[$i]['quantity']);
    $show_min_units = zen_get_products_quantity_min_units_display($products[$i]['id']);
    $quantity_field = zen_draw_input_field('cart_quantity[]', $products[$i]['quantity'], 'size="4" class="cart_input_' . $products[$i]['id'] . '" aria-label="' . ARIA_EDIT_QTY_IN_CART . '"');
    // $ppe is product price each, before one-time charges added
    $ppe = $products[$i]['final_price'];
    $ppe = zen_add_tax($ppe, zen_get_tax_rate($products[$i]['tax_class_id']));
    // $ppt is product price total, before one-time charges added
    $ppt = $ppe * $products[$i]['quantity'];
    $products_price_each = $currencies->format($ppe) . ($products[$i]['onetime_charges'] != 0 ? '<br>' . $currencies->display_price($products[$i]['onetime_charges'], zen_get_tax_rate($products[$i]['tax_class_id']), 1) : '');
    $products_price_total = $currencies->format($ppt) . ($products[$i]['onetime_charges'] != 0 ? '<br>' . $currencies->display_price($products[$i]['onetime_charges'], zen_get_tax_rate($products[$i]['tax_class_id']), 1) : '');
    $button_delete = true;
    $check_box_delete = true;
    if (SHOW_SHOPPING_CART_DELETE == 1) {
        $check_box_delete = false;
    } elseif (SHOW_SHOPPING_CART_DELETE == 2) {
        $button_delete = false;
    }
    $button_update = '';
    if (SHOW_SHOPPING_CART_UPDATE == 1 or SHOW_SHOPPING_CART_UPDATE == 3) {
        if (!$show_fixed_quantity) {
            $button_update = zen_image_submit(ICON_IMAGE_UPDATE, ICON_UPDATE_ALT);
        } else {
            $button_update = zen_image_submit(ICON_IMAGE_UPDATE, ICON_UPDATE_ALT, 'style="opacity: 0.25" disabled="disabled"');
        }
    }
    $button_update .= zen_draw_hidden_field('products_id[]', $products[$i]['id']);
    $product_array[$i] = ['attributeHiddenField' => $attribute_hidden_field, 'flagStockCheck' => $flag_stock_check, 'flagShowFixedQuantity' => $show_fixed_quantity, 'linkProductsImage' => $link_products_image, 'linkProductsName' => $link_products_name, 'productsImage' => $products_image, 'productsName' => $products_name, 'productsModel' => $products_model, 'showFixedQuantity' => $show_fixed_quantity, 'showFixedQuantityAmount' => $show_fixed_quantity_amount, 'showMinUnits' => $show_min_units, 'quantityField' => $quantity_field, 'buttonUpdate' => $button_update, 'productsPrice' => $products_price_total, 'productsPriceEach' => $products_price_each, 'rowClass' => $row_class, 'buttonDelete' => $button_delete, 'checkBoxDelete' => $check_box_delete, 'id' => $products[$i]['id'], 'attributes' => empty($attr_array) ? false : $attr_array];
    $zco_notifier->notify('NOTIFY_HEADER_SHOPPING_CART_IN_PRODUCTS_LOOP', $i, $product_array);
}
// end FOR loop
$zco_notifier->notify('NOTIFY_HEADER_SHOPPING_CART_AFTER_PRODUCTS_LOOP', $product_array);
$flag_has_cart_contents = $number_of_items_in_cart > 0;
$cart_show_total = $currencies->format($cart_total_price);
// build shipping/items message with Tare included. We do this here in case any custom product stuff needs to alter the original values from the cart class
$totals_display = '';
switch (SHOW_TOTALS_IN_CART) {
    case '1':
        $totals_display = TEXT_TOTAL_ITEMS . $number_of_items_in_cart . TEXT_TOTAL_WEIGHT . $shipping_weight . TEXT_PRODUCT_WEIGHT_UNIT . TEXT_TOTAL_AMOUNT . $cart_show_total;
        break;
    case '2':
        $totals_display = TEXT_TOTAL_ITEMS . $number_of_items_in_cart . ($shipping_weight > 0 ? TEXT_TOTAL_WEIGHT . $shipping_weight . TEXT_PRODUCT_WEIGHT_UNIT : '') . TEXT_TOTAL_AMOUNT . $cart_show_total;
        break;
    case '3':
        $totals_display = TEXT_TOTAL_ITEMS . $number_of_items_in_cart . TEXT_TOTAL_AMOUNT . $cart_show_total;
        break;
}
$define_page = zen_get_file_directory(DIR_WS_LANGUAGES . $_SESSION['language'] . '/html_includes/', FILENAME_DEFINE_SHOPPING_CART, 'false');
// This should be last line of the script:
$zco_notifier->notify('NOTIFY_HEADER_END_SHOPPING_CART');