<?php

/**
 * Module Template - for shipping-estimator display
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: piloujp 2025 Oct 13 Modified in v2.2.0 $
 */
if ($_SESSION['cart']->count_contents() === 0) {
    return;
}
if (empty($extra)) {
    $extra = '';
} else {
    $extra = ' class="' . $extra . '"';
}
?>
<div id="shippingEstimatorContent">
    <?php 
echo zen_draw_form('estimator', zen_href_link($show_in . '#seView', '', $request_type), 'post');
if (is_array($selected_shipping)) {
    echo zen_draw_hidden_field('scid', $selected_shipping['id']);
}
echo zen_draw_hidden_field('action', 'submit');
?>
    <h2><?php 
echo CART_SHIPPING_OPTIONS;
?></h2>
<?php 
if (!empty($totals_display)) {
    ?>
    <div class="cartTotalsDisplay important"><?php 
    echo $totals_display;
    ?></div>
<?php 
}
if (zen_is_logged_in() && !zen_in_guest_checkout()) {
    // only display addresses if more than 1
    if ($addresses->record_count() > 1) {
        ?>
    <div class="se-address-container">
    <label for="seAddressPulldown"><?php 
        echo CART_SHIPPING_METHOD_ADDRESS;
        ?></label>
    <?php 
        echo zen_draw_pull_down_menu('address_id', $addresses_array, $selected_address, 'onchange="return shipincart_submit();" id="seAddressPulldown"');
    }
    ?>
    <div class="se-address">
    <div class="bold" id="seShipTo"><?php 
    echo CART_SHIPPING_METHOD_TO;
    ?></div>
    <address>
        <?php 
    echo zen_address_format($order->delivery['format_id'], $order->delivery, 1, ' ', '<br>');
    ?>
    </address>
    </div>
    </div>
<?php 
} elseif ($_SESSION['cart']->get_content_type() !== 'virtual') {
    $flag_show_pulldown_states = ACCOUNT_STATE_DRAW_INITIAL_DROPDOWN === 'true';
    ?>
    <label class="inputLabel" for="country"><?php 
    echo ENTRY_COUNTRY;
    ?></label>
    <?php 
    echo zen_get_country_list('zone_country_id', $selected_country, 'id="country"' . ($flag_show_pulldown_states ? ' onchange="update_zone(this.form);"' : ''));
    ?>
    <br class="clearBoth">

    <a id="seView"></a>
    <label class="inputLabel" for="stateZone" id="zoneLabel"><?php 
    echo ENTRY_STATE;
    ?></label>
<?php 
    if ($flag_show_pulldown_states) {
        ?>
    <?php 
        echo zen_draw_pull_down_menu('zone_id', zen_prepare_country_zones_pull_down($selected_country), $state_zone_id, 'id="stateZone"');
        ?>
    <br class="clearBoth" id="stBreak">
<?php 
    }
    ?>
    <label class="inputLabel" for="state" id="stateLabel"><?php 
    echo $state_field_label ?? '';
    ?></label>
    <?php 
    echo zen_draw_input_field('state', $selected_state, zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_state', '40') . ' id="state"') . '&nbsp;<span class="alert" id="stText">&nbsp;</span>';
    ?>
    <br class="clearBoth">
<?php 
    if (CART_SHIPPING_METHOD_ZIP_REQUIRED === 'true') {
        ?>
    <label class="inputLabel" for="postcode"><?php 
        echo ENTRY_POST_CODE;
        ?></label>
    <?php 
        echo zen_draw_input_field('postcode', $postcode, 'size="7" id="postcode"');
        ?>
    <br class="clearBoth">
<?php 
    }
    ?>
    <div class="buttonRow forward"><?php 
    echo zen_image_submit(BUTTON_IMAGE_UPDATE, BUTTON_UPDATE_ALT);
    ?></div>
    <br class="clearBoth">
<?php 
}
echo '</form>';
if ($_SESSION['cart']->get_content_type() === 'virtual') {
    echo CART_SHIPPING_METHOD_FREE_TEXT . ' ' . CART_SHIPPING_METHOD_ALL_DOWNLOADS;
} elseif ($free_shipping == 1) {
    echo sprintf(FREE_SHIPPING_DESCRIPTION, $currencies->format(MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER));
} else {
    ?>
    <table id="seQuoteResults">
<?php 
    if (!zen_is_logged_in() || zen_in_guest_checkout()) {
        ?>
        <tr>
            <td colspan="2" class="seDisplayedAddressLabel">
                <?php 
        echo CART_SHIPPING_QUOTE_CRITERIA;
        ?><br>
                <?php 
        echo '<span class="seDisplayedAddressInfo">' . zen_get_zone_name((int) $selected_country, (int) $state_zone_id, '') . ($selected_state != '' ? ' ' . $selected_state : '') . ' ' . ($order->delivery['postcode'] ?? '') . ' ' . zen_get_country_name($order->delivery['country_id']) . '</span>';
        ?>
            </td>
        </tr>
<?php 
    }
    ?>
        <tr>
            <th scope="col" id="seProductsHeading"><?php 
    echo CART_SHIPPING_METHOD_TEXT;
    ?></th>
            <th scope="col" id="seTotalHeading"><?php 
    echo CART_SHIPPING_METHOD_RATES;
    ?></th>
        </tr>
<?php 
    foreach ($quotes as $next_module) {
        $thisquoteid = '';
        if (empty($next_module['module'])) {
            continue;
        }
        if (!empty($next_module['error'])) {
            ?>
        <tr<?php 
            echo $extra;
            ?>>
            <td colspan="2">
                <?php 
            echo $next_module['module'];
            ?>
                <?php 
            echo !empty($next_module['icon']) ? $next_module['icon'] : '';
            ?>
                &nbsp;<?php 
            echo $next_module['error'];
            ?>
            </td>
        </tr>
<?php 
            continue;
        }
        if (empty($next_module['methods'])) {
            continue;
        }
        if (!is_array($next_module['methods'])) {
            continue;
        }
        // shipping method with sub methods (multipickup) or none
        foreach ($next_module['methods'] as $next_method) {
            $thisquoteid = $next_module['id'] . '_' . $next_method['id'];
            $extra_class = $selected_shipping['id'] === $thisquoteid ? 'bold' : '';
            ?>
        <tr<?php 
            echo $extra;
            ?>>
            <td class="<?php 
            echo $extra_class;
            ?>">
                <?php 
            echo $next_module['module'];
            ?>&nbsp;(<?php 
            echo $next_method['title'];
            ?>)
            </td>
            <td class="cartTotalDisplay <?php 
            echo $extra_class;
            ?>">
                <?php 
            echo $currencies->format(zen_add_tax($next_method['cost'], $next_module['tax'] ?? 0));
            ?>
            </td>
       </tr>
<?php 
        }
        ?>

<?php 
    }
    ?>
    </table>
<?php 
    if (empty($quotes)) {
        ?>
        <div id="noShippingAvailable" class="alert important">
            <?php 
        echo TEXT_NO_SHIPPING_AVAILABLE_ESTIMATOR;
        ?>
        </div>
<?php 
    }
}
?>
</div>
