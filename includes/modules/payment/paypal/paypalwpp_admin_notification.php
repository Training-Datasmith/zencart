<?php

declare (strict_types=1);
/**
 * paypalwpp_admin_notification.php admin display component
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @copyright Portions Copyright 2004 DevosC.com
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: lat9 2025 Aug 13 Modified in v2.2.0 $
 */
if (!defined('TEXT_MAXIMUM_CHARACTERS_ALLOWED')) {
    define('TEXT_MAXIMUM_CHARACTERS_ALLOWED', ' chars allowed');
}
$output_pay_pal = '';
$output_p_fmain = '';
$output_auth = '';
$output_capt = '';
$output_void = '';
$output_refund = '';
// strip slashes in case they were added to handle apostrophes, noting that some of the fields
// from the "paypal" table might be NULL:
foreach ($ipn->fields as $key => $value) {
    $ipn->fields[$key] = $value === null ? '' : stripslashes($value);
}
if (!empty($response['RESPMSG'])) {
    // these would be payflow transactions
    $output_p_fmain .= '<td style="vertical-align: top"><table id="outputPFmain">' . "\n";
    $output_p_fmain .= '<tr><td>' . "\n";
    $output_p_fmain .= MODULE_PAYMENT_PAYPAL_ENTRY_AUTHCODE . "\n";
    $output_p_fmain .= '</td><td>' . "\n";
    $output_p_fmain .= $response['AUTHCODE'] . "\n";
    $output_p_fmain .= '</td></tr>' . "\n";
    $output_p_fmain .= '<tr><td>' . "\n";
    $output_p_fmain .= MODULE_PAYMENT_PAYPAL_ENTRY_PAYMENT_STATUS . "\n";
    $output_p_fmain .= '</td><td>' . "\n";
    $output_p_fmain .= $response['RESPMSG'] . "\n";
    $output_p_fmain .= '</td></tr>' . "\n";
    $output_p_fmain .= '<tr><td>' . "\n";
    $output_p_fmain .= MODULE_PAYMENT_PAYPAL_ENTRY_AVSADDR . "\n";
    $output_p_fmain .= '</td><td>' . "\n";
    $output_p_fmain .= $response['AVSADDR'] . "\n";
    $output_p_fmain .= '</td></tr>' . "\n";
    $output_p_fmain .= '<tr><td>' . "\n";
    $output_p_fmain .= MODULE_PAYMENT_PAYPAL_ENTRY_AVSZIP . "\n";
    $output_p_fmain .= '</td><td>' . "\n";
    $output_p_fmain .= $response['AVSZIP'] . "\n";
    $output_p_fmain .= '</td></tr>' . "\n";
    $output_p_fmain .= '<tr><td>' . "\n";
    $output_p_fmain .= MODULE_PAYMENT_PAYPAL_ENTRY_CVV2MATCH . "\n";
    $output_p_fmain .= '</td><td>' . "\n";
    $output_p_fmain .= $response['CVV2MATCH'] . "\n";
    $output_p_fmain .= '</td></tr>' . "\n";
    $output_p_fmain .= '<tr><td>' . "\n";
    $output_p_fmain .= MODULE_PAYMENT_PAYPAL_ENTRY_TXN_ID . "\n";
    $output_p_fmain .= '</td><td>' . "\n";
    $output_p_fmain .= $response['ORIGPNREF'] . "\n";
    $output_p_fmain .= '</td></tr>' . "\n";
    $output_p_fmain .= '<tr><td>' . "\n";
    $output_p_fmain .= MODULE_PAYMENT_PAYPAL_ENTRY_PAYMENT_DATE . "\n";
    $output_p_fmain .= '</td><td>' . "\n";
    $output_p_fmain .= $ipn->fields['payment_date'] . "\n";
    $output_p_fmain .= '</td></tr>' . "\n";
    $output_p_fmain .= '<tr><td>' . "\n";
    $output_p_fmain .= MODULE_PAYMENT_PAYPAL_ENTRY_TRANSSTATE . "\n";
    $output_p_fmain .= '</td><td>' . "\n";
    $output_p_fmain .= $response['TRANSSTATE'] . "\n";
    $output_p_fmain .= '</td></tr>' . "\n";
    if (!empty($response['DAYS_TO_SETTLE'])) {
        $output_p_fmain .= '<tr><td>' . "\n";
        $output_p_fmain .= MODULE_PAYMENT_PAYPAL_ENTRY_DAYSTOSETTLE . "\n";
        $output_p_fmain .= '</td><td>' . "\n";
        $output_p_fmain .= $response['DAYS_TO_SETTLE'] . "\n";
        $output_p_fmain .= '</td></tr>' . "\n";
    }
    $output_p_fmain .= '</table></td>' . "\n\n";
    if ($ipn->fields['mc_gross'] > 0) {
        $output_p_fmain .= '<td style="vertical-align: top"><table id="outputPFmain-mc_gross">' . "\n";
        $output_p_fmain .= '<tr><td>' . "\n";
        $output_p_fmain .= MODULE_PAYMENT_PAYPAL_ENTRY_CURRENCY . "\n";
        $output_p_fmain .= '</td><td>' . "\n";
        $output_p_fmain .= $ipn->fields['mc_currency'] . "\n";
        $output_p_fmain .= '</td></tr>' . "\n";
        $output_p_fmain .= '<tr><td>' . "\n";
        $output_p_fmain .= MODULE_PAYMENT_PAYPAL_ENTRY_GROSS_AMOUNT . "\n";
        $output_p_fmain .= '</td><td>' . "\n";
        $output_p_fmain .= $ipn->fields['mc_gross'] . "\n";
        $output_p_fmain .= '</td></tr>' . "\n";
        $output_p_fmain .= '<tr><td>' . "\n";
        $output_p_fmain .= MODULE_PAYMENT_PAYPAL_ENTRY_PAYMENT_FEE . "\n";
        $output_p_fmain .= '</td><td>' . "\n";
        $output_p_fmain .= $ipn->fields['mc_fee'] . "\n";
        $output_p_fmain .= '</td></tr>' . "\n";
        $output_p_fmain .= '<tr><td>' . "\n";
        $output_p_fmain .= MODULE_PAYMENT_PAYPAL_ENTRY_EXCHANGE_RATE . "\n";
        $output_p_fmain .= '</td><td>' . "\n";
        $output_p_fmain .= $ipn->fields['exchange_rate'] . "\n";
        $output_p_fmain .= '</td></tr>' . "\n";
        $output_p_fmain .= '<tr><td>' . "\n";
        $output_p_fmain .= MODULE_PAYMENT_PAYPAL_ENTRY_CART_ITEMS . "\n";
        $output_p_fmain .= '</td><td>' . "\n";
        $output_p_fmain .= $ipn->fields['num_cart_items'] . "\n";
        $output_p_fmain .= '</td></tr>' . "\n";
        $output_p_fmain .= '</table></td>' . "\n\n";
    }
} elseif ($response === false) {
    $output_pay_pal .= '<td style="vertical-align: top">n/a</td>' . "\n\n";
} else {
    // display all paypal status fields (in admin Orders page):
    $output_pay_pal .= '<td style="vertical-align: top"><table id="outputPayPal_1">' . "\n";
    $output_pay_pal .= '<tr><td>' . "\n";
    $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_FIRST_NAME . "\n";
    $output_pay_pal .= '</td><td>' . "\n";
    $output_pay_pal .= urldecode($response['FIRSTNAME'] ?? '') . "\n";
    $output_pay_pal .= '</td></tr>' . "\n";
    $output_pay_pal .= '<tr><td>' . "\n";
    $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_LAST_NAME . "\n";
    $output_pay_pal .= '</td><td>' . "\n";
    $output_pay_pal .= urldecode($response['LASTNAME'] ?? '') . "\n";
    $output_pay_pal .= '</td></tr>' . "\n";
    if (!empty($response['BUSINESS'])) {
        $output_pay_pal .= '<tr><td>' . "\n";
        $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_BUSINESS_NAME . "\n";
        $output_pay_pal .= '</td><td>' . "\n";
        $output_pay_pal .= urldecode((string) $response['BUSINESS']) . "\n";
        $output_pay_pal .= '</td></tr>' . "\n";
    }
    $optional_fields = ['SHIPTONAME', 'SHIPTOSTREET', 'SHIPTOCITY', 'SHIPTOSTATE', 'SHIPTOZIP', 'SHIPTOCOUNTRYNAME', 'FEEAMT'];
    foreach ($optional_fields as $optional) {
        if (!isset($response[$optional])) {
            $response[$optional] = 'n/a';
        }
    }
    $output_pay_pal .= '<tr><td>' . "\n";
    $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_ADDRESS_NAME . "\n";
    $output_pay_pal .= '</td><td>' . "\n";
    $output_pay_pal .= urldecode((string) $response['SHIPTONAME']) . "\n";
    $output_pay_pal .= '</td></tr>' . "\n";
    $output_pay_pal .= '<tr><td>' . "\n";
    $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_ADDRESS_STREET . "\n";
    $output_pay_pal .= '</td><td>' . "\n";
    $output_pay_pal .= urldecode((string) $response['SHIPTOSTREET']) . ' ' . (!empty($response['SHIPTOSTREET2']) ? urldecode((string) $response['SHIPTOSTREET2']) : '') . "\n";
    $output_pay_pal .= '</td></tr>' . "\n";
    $output_pay_pal .= '<tr><td>' . "\n";
    $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_ADDRESS_CITY . "\n";
    $output_pay_pal .= '</td><td>' . "\n";
    $output_pay_pal .= urldecode((string) $response['SHIPTOCITY']) . "\n";
    $output_pay_pal .= '</td></tr>' . "\n";
    $output_pay_pal .= '<tr><td>' . "\n";
    $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_ADDRESS_STATE . "\n";
    $output_pay_pal .= '</td><td>' . "\n";
    $output_pay_pal .= urldecode((string) $response['SHIPTOSTATE']) . ' ' . urldecode((string) $response['SHIPTOZIP']) . "\n";
    $output_pay_pal .= '</td></tr>' . "\n";
    $output_pay_pal .= '<tr><td>' . "\n";
    $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_ADDRESS_COUNTRY . "\n";
    $output_pay_pal .= '</td><td>' . "\n";
    $output_pay_pal .= urldecode((string) $response['SHIPTOCOUNTRYNAME']) . "\n";
    $output_pay_pal .= '</td></tr>' . "\n";
    $output_pay_pal .= '</table></td>' . "\n\n";
    $output_pay_pal .= '<td style="vertical-align: top"><table id="outputPayPal_2">' . "\n";
    $output_pay_pal .= '<tr><td>' . "\n";
    $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_EMAIL_ADDRESS . "\n";
    $output_pay_pal .= '</td><td>' . "\n";
    $output_pay_pal .= urldecode($response['EMAIL'] ?? '') . "\n";
    $output_pay_pal .= '</td></tr>' . "\n";
    $output_pay_pal .= '<tr><td>' . "\n";
    $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_EBAY_ID . "\n";
    $output_pay_pal .= '</td><td>' . "\n";
    $output_pay_pal .= (!empty($response['BUYERID']) ? urldecode((string) $response['BUYERID']) : '') . "\n";
    $output_pay_pal .= '</td></tr>' . "\n";
    $output_pay_pal .= '<tr><td>' . "\n";
    $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_PAYER_ID . "\n";
    $output_pay_pal .= '</td><td>' . "\n";
    $output_pay_pal .= urldecode($response['PAYERID'] ?? '') . "\n";
    $output_pay_pal .= '</td></tr>' . "\n";
    $output_pay_pal .= '<tr><td>' . "\n";
    $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_PAYER_STATUS . "\n";
    $output_pay_pal .= '</td><td>' . "\n";
    $output_pay_pal .= urldecode($response['PAYERSTATUS'] ?? '') . "\n";
    $output_pay_pal .= '</td></tr>' . "\n";
    $output_pay_pal .= '<tr><td>' . "\n";
    $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_ADDRESS_STATUS . "\n";
    $output_pay_pal .= '</td><td>' . "\n";
    $output_pay_pal .= urldecode($response['ADDRESSSTATUS'] ?? '') . "\n";
    $output_pay_pal .= '</td></tr>' . "\n";
    $output_pay_pal .= '<tr><td>' . "\n";
    $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_TXN_ID . "\n";
    $output_pay_pal .= '</td><td>' . "\n";
    if (isset($response['TRANSACTIONID'])) {
        $output_pay_pal .= '<a href="https://www.paypal.com/us/cgi-bin/webscr?cmd=_view-a-trans&amp;id=' . urldecode($response['TRANSACTIONID']) . '" rel="noopener" target="_blank">' . urldecode($response['TRANSACTIONID']) . '</a>' . "\n";
    } else {
        $output_pay_pal .= 'n/a';
    }
    $output_pay_pal .= '</td></tr>' . "\n";
    $output_pay_pal .= '<tr><td>' . "\n";
    $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_PARENT_TXN_ID . "\n";
    $output_pay_pal .= '</td><td>' . "\n";
    $output_pay_pal .= (!empty($response['PARENTTRANSACTIONID']) ? urldecode((string) $response['PARENTTRANSACTIONID']) : '') . "\n";
    $output_pay_pal .= '</td></tr>' . "\n";
    if (defined('MODULE_PAYMENT_PAYPALWPP_ENTRY_PROTECTIONELIG') && !empty($response['PROTECTIONELIGIBILITY'])) {
        $output_pay_pal .= '<tr><td>' . "\n";
        $output_pay_pal .= MODULE_PAYMENT_PAYPALWPP_ENTRY_PROTECTIONELIG . "\n";
        $output_pay_pal .= '</td><td>' . "\n";
        $output_pay_pal .= $response['PROTECTIONELIGIBILITY'] . "\n";
        $output_pay_pal .= '</td></tr>' . "\n";
    }
    if (defined('MODULE_PAYMENT_PAYPAL_ENTRY_COMMENTS') && !empty($ipn->fields['memo'])) {
        $output_pay_pal .= '<tr><td>' . "\n";
        $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_COMMENTS . "\n";
        $output_pay_pal .= '</td><td>' . "\n";
        $output_pay_pal .= $ipn->fields['memo'] . "\n";
        $output_pay_pal .= '</td></tr>' . "\n";
    }
    $output_pay_pal .= '</table></td>' . "\n\n";
    $output_pay_pal .= '<td style="vertical-align: top"><table id="outputPayPal_3">' . "\n";
    $output_pay_pal .= '<tr><td>' . "\n";
    $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_TXN_TYPE . "\n";
    $output_pay_pal .= '</td><td>' . "\n";
    $output_pay_pal .= urldecode($response['TRANSACTIONTYPE'] ?? '') . "\n";
    $output_pay_pal .= '</td></tr>' . "\n";
    $output_pay_pal .= '<tr><td>' . "\n";
    $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_PAYMENT_TYPE . "\n";
    $output_pay_pal .= '</td><td>' . "\n";
    $output_pay_pal .= urldecode($response['PAYMENTTYPE'] ?? '') . "\n";
    $output_pay_pal .= '</td></tr>' . "\n";
    $output_pay_pal .= '<tr><td>' . "\n";
    $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_PAYMENT_STATUS . "\n";
    $output_pay_pal .= '</td><td>' . "\n";
    $output_pay_pal .= urldecode($response['PAYMENTSTATUS'] ?? '') . "\n";
    $output_pay_pal .= '</td></tr>' . "\n";
    $output_pay_pal .= '<tr><td>' . "\n";
    $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_PENDING_REASON . "\n";
    $output_pay_pal .= '</td><td>' . "\n";
    if (isset($response['PENDINGREASON'])) {
        $output_pay_pal .= urldecode($response['PENDINGREASON']) . (empty($response['REASONCODE']) || $response['REASONCODE'] == 'None' ? '' : urldecode($response['PENDINGREASON'])) . "\n";
    } else {
        $output_pay_pal .= 'n/a';
    }
    $output_pay_pal .= '</td></tr>' . "\n";
    $output_pay_pal .= '<tr><td>' . "\n";
    $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_INVOICE . "\n";
    $output_pay_pal .= '</td><td>' . "\n";
    if (!empty($ipn->fields['invoice'])) {
        $output_pay_pal .= urldecode((string) $ipn->fields['invoice']) . (urldecode((string) $ipn->fields['invoice']) != urldecode($response['INVNUM'] ?? '') ? '<br>' . urldecode($response['INVNUM'] ?? '') : '') . "\n";
    } else {
        $output_pay_pal .= 'n/a';
    }
    $output_pay_pal .= '</td></tr>' . "\n";
    $output_pay_pal .= '<tr><td>' . "\n";
    $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_PAYMENT_DATE . "\n";
    $output_pay_pal .= '</td><td>' . "\n";
    $output_pay_pal .= urldecode($response['ORDERTIME'] ?? '') . "\n";
    $output_pay_pal .= '</td></tr>' . "\n";
    $output_pay_pal .= '</table></td>' . "\n\n";
    $output_pay_pal .= '<td style="vertical-align: top"><table id="outputPayPal_4">' . "\n";
    $output_pay_pal .= '<tr><td>' . "\n";
    $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_CURRENCY . "\n";
    $output_pay_pal .= '</td><td>' . "\n";
    if (!empty($ipn->fields['mc_currency']) && !empty($response['CURRENCYCODE'])) {
        $output_pay_pal .= $ipn->fields['mc_currency'] . "\n";
        if ($ipn->fields['mc_currency'] !== urldecode((string) $response['CURRENCYCODE'])) {
            $output_pay_pal .= ' ' . urldecode((string) $response['CURRENCYCODE']);
        }
    } else {
        $output_pay_pal .= 'n/a';
    }
    $output_pay_pal .= "\n";
    $output_pay_pal .= '</td></tr>' . "\n";
    $output_pay_pal .= '<tr><td>' . "\n";
    $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_GROSS_AMOUNT . "\n";
    $output_pay_pal .= '</td><td>' . "\n";
    $output_pay_pal .= urldecode($response['AMT'] ?? '') . "\n";
    $output_pay_pal .= '</td></tr>' . "\n";
    $output_pay_pal .= '<tr><td>' . "\n";
    $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_PAYMENT_FEE . "\n";
    $output_pay_pal .= '</td><td>' . "\n";
    $output_pay_pal .= urldecode((string) $response['FEEAMT']) . "\n";
    $output_pay_pal .= '</td></tr>' . "\n";
    $output_pay_pal .= '<tr><td>' . "\n";
    $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_EXCHANGE_RATE . "\n";
    $output_pay_pal .= '</td><td>' . "\n";
    $output_pay_pal .= (!empty($response['EXCHANGERATE']) ? urldecode((string) $response['EXCHANGERATE']) : '') . "\n";
    $output_pay_pal .= '</td></tr>' . "\n";
    $output_pay_pal .= '<tr><td>' . "\n";
    $output_pay_pal .= MODULE_PAYMENT_PAYPAL_ENTRY_CART_ITEMS . "\n";
    $output_pay_pal .= '</td><td>' . "\n";
    $output_pay_pal .= ($ipn->fields['num_cart_items'] ?? '') . "\n";
    $output_pay_pal .= '</td></tr>' . "\n";
    $output_pay_pal .= '</table></td>' . "\n\n";
}
if (method_exists($this, '_doRefund')) {
    $output_refund .= '<td><table id="outputRefund" class="noprint">' . "\n";
    $output_refund .= '<tr style="background-color: #eeeeee;border: solid thin black;">' . "\n";
    $output_refund .= '<td>' . MODULE_PAYMENT_PAYPAL_ENTRY_REFUND_TITLE . '<br>' . "\n";
    $output_refund .= zen_draw_form('pprefund', FILENAME_ORDERS, zen_get_all_get_params(['action']) . 'action=doRefund', 'post') . zen_hide_session_id();
    if (!isset($response['RESPMSG'])) {
        // full refund (only for PayPal transactions, not Payflow)
        $output_refund .= MODULE_PAYMENT_PAYPAL_ENTRY_REFUND_FULL;
        $output_refund .= '<br>' . MODULE_PAYMENT_PAYPALWPP_TEXT_REFUND_FULL_CONFIRM_CHECK . zen_draw_checkbox_field('reffullconfirm', '', false, '') . ' <input type="submit" id="fullrefund" name="fullrefund" value="' . MODULE_PAYMENT_PAYPAL_ENTRY_REFUND_BUTTON_TEXT_FULL . '" title="' . MODULE_PAYMENT_PAYPAL_ENTRY_REFUND_BUTTON_TEXT_FULL . '">';
        $output_refund .= '<script>$("#reffullconfirm").change(function () {$("#fullrefund").prop("disabled", !this.checked);}).change()</script>';
        $output_refund .= MODULE_PAYMENT_PAYPAL_ENTRY_REFUND_TEXT_FULL_OR;
    } else {
        $output_refund .= MODULE_PAYMENT_PAYPAL_ENTRY_REFUND_PAYFLOW_TEXT;
    }
    //partial refund - input field
    $output_refund .= MODULE_PAYMENT_PAYPAL_ENTRY_REFUND_PARTIAL_TEXT . ' ' . zen_draw_input_field('refamt', '', 'size="8"');
    $output_refund .= '<input type="submit" name="partialrefund" value="' . MODULE_PAYMENT_PAYPAL_ENTRY_REFUND_BUTTON_TEXT_PARTIAL . '" title="' . MODULE_PAYMENT_PAYPAL_ENTRY_REFUND_BUTTON_TEXT_PARTIAL . '"><br>';
    //comment field
    $counter_params = 'onkeydown="characterCount(this.form[\'refnote\'],this.form.remainingRefund,255);" onkeyup="characterCount(this.form[\'refnote\'],this.form.remainingRefund,255);"';
    $output_refund .= '<br>' . MODULE_PAYMENT_PAYPAL_ENTRY_REFUND_TEXT_COMMENTS;
    $output_refund .= '<div style="text-align:right;margin-top:-1.2em"><input disabled="disabled" type="text" name="remainingRefund" size="2" maxlength="3" value="255"> ' . TEXT_MAXIMUM_CHARACTERS_ALLOWED . '</div>';
    $output_refund .= zen_draw_textarea_field('refnote', 'soft', '50', '3', MODULE_PAYMENT_PAYPAL_ENTRY_REFUND_DEFAULT_MESSAGE, $counter_params);
    //message text
    $output_refund .= '<br>' . MODULE_PAYMENT_PAYPAL_ENTRY_REFUND_SUFFIX;
    $output_refund .= '</form>';
    $output_refund .= '</td></tr></table></td>' . "\n\n";
}
if (method_exists($this, '_doAuth') && !isset($response['RESPMSG'])) {
    $output_auth .= '<td style="vertical-align: top"><table id="outputAuth" class="noprint">' . "\n";
    $output_auth .= '<tr style="background-color: #eeeeee;border: solid thin black;">' . "\n";
    $output_auth .= '<td>' . MODULE_PAYMENT_PAYPAL_ENTRY_AUTH_TITLE . '<br>' . "\n";
    $output_auth .= zen_draw_form('ppauth', FILENAME_ORDERS, zen_get_all_get_params(['action']) . 'action=doAuth', 'post');
    //partial auth - input field
    $output_auth .= '<br>' . MODULE_PAYMENT_PAYPAL_ENTRY_AUTH_PARTIAL_TEXT . ' ' . zen_draw_input_field('authamt', 'enter amount', 'length="8"') . zen_hide_session_id();
    $output_auth .= '<input type="submit" name="orderauth" value="' . MODULE_PAYMENT_PAYPAL_ENTRY_AUTH_BUTTON_TEXT_PARTIAL . '" title="' . MODULE_PAYMENT_PAYPAL_ENTRY_AUTH_BUTTON_TEXT_PARTIAL . '">' . MODULE_PAYMENT_PAYPALWPP_TEXT_AUTH_FULL_CONFIRM_CHECK . zen_draw_checkbox_field('authconfirm', '', false) . '<br>';
    //message text
    $output_auth .= '<br>' . MODULE_PAYMENT_PAYPAL_ENTRY_AUTH_SUFFIX;
    $output_auth .= '</form>';
    $output_auth .= '</td></tr></table></td>' . "\n\n";
}
if (method_exists($this, '_doCapt')) {
    $output_capt .= '<td style="vertical-align: top"><table id="outputCapt" class="noprint">' . "\n";
    $output_capt .= '<tr style="background-color: #eeeeee;border: solid thin black;">' . "\n";
    $output_capt .= '<td>' . MODULE_PAYMENT_PAYPAL_ENTRY_CAPTURE_TITLE . '<br>' . "\n";
    $output_capt .= zen_draw_form('ppcapture', FILENAME_ORDERS, zen_get_all_get_params(['action']) . 'action=doCapture', 'post') . zen_hide_session_id();
    $output_capt .= MODULE_PAYMENT_PAYPAL_ENTRY_CAPTURE_FULL;
    $output_capt .= '<br>' . MODULE_PAYMENT_PAYPAL_ENTRY_CAPTURE_AMOUNT_TEXT . ' ' . zen_draw_input_field('captamt', 'enter amount', 'length="8"');
    $output_capt .= '<br>' . MODULE_PAYMENT_PAYPAL_ENTRY_CAPTURE_FINAL_TEXT . ' ' . zen_draw_checkbox_field('captfinal', '', true) . '<br>';
    $output_capt .= '<input type="submit" name="btndocapture" value="' . MODULE_PAYMENT_PAYPAL_ENTRY_CAPTURE_BUTTON_TEXT_FULL . '" title="' . MODULE_PAYMENT_PAYPAL_ENTRY_CAPTURE_BUTTON_TEXT_FULL . '">' . ' ' . MODULE_PAYMENT_PAYPALWPP_TEXT_REFUND_FULL_CONFIRM_CHECK . zen_draw_checkbox_field('captfullconfirm', '', false);
    //comment field
    $output_capt .= '<br>' . MODULE_PAYMENT_PAYPAL_ENTRY_CAPTURE_TEXT_COMMENTS . '<br>' . zen_draw_textarea_field('captnote', 'soft', '50', '2', MODULE_PAYMENT_PAYPAL_ENTRY_CAPTURE_DEFAULT_MESSAGE);
    //message text
    $output_capt .= '<br>' . MODULE_PAYMENT_PAYPAL_ENTRY_CAPTURE_SUFFIX;
    $output_capt .= '</form>';
    $output_capt .= '</td></tr></table></td>' . "\n\n";
}
if (method_exists($this, '_doVoid')) {
    $output_void .= '<td style="vertical-align: top"><table id="outputVoid" class="noprint">' . "\n";
    $output_void .= '<tr style="background-color: #eeeeee;border: solid thin black;">' . "\n";
    $output_void .= '<td>' . MODULE_PAYMENT_PAYPAL_ENTRY_VOID_TITLE . '<br>' . "\n";
    $output_void .= zen_draw_form('ppvoid', FILENAME_ORDERS, zen_get_all_get_params(['action']) . 'action=doVoid', 'post') . zen_hide_session_id();
    $output_void .= MODULE_PAYMENT_PAYPAL_ENTRY_VOID . '<br>' . zen_draw_input_field('voidauthid', '', 'size="16"');
    $output_void .= MODULE_PAYMENT_PAYPALWPP_TEXT_VOID_CONFIRM_CHECK . zen_draw_checkbox_field('voidconfirm', '', false, '') . ' ' . '<input type="submit" id="ordervoid" name="ordervoid" value="' . MODULE_PAYMENT_PAYPAL_ENTRY_VOID_BUTTON_TEXT_FULL . '" title="' . MODULE_PAYMENT_PAYPAL_ENTRY_VOID_BUTTON_TEXT_FULL . '">';
    $output_void .= '<script>$("#voidconfirm").change(function () {$("#ordervoid").prop("disabled", !this.checked);}).change()</script>';
    //comment field
    $output_void .= '<br>' . MODULE_PAYMENT_PAYPAL_ENTRY_VOID_TEXT_COMMENTS . '<br>' . zen_draw_textarea_field('voidnote', 'soft', '50', '3', MODULE_PAYMENT_PAYPAL_ENTRY_VOID_DEFAULT_MESSAGE);
    //message text
    $output_void .= '<br>' . MODULE_PAYMENT_PAYPAL_ENTRY_VOID_SUFFIX;
    $output_void .= '</form>';
    $output_void .= '</td></tr></table></td>' . "\n\n";
}
//reused components
$output_start_block = '<table class="noprint">' . "\n" . '<tr style="background-color: #cccccc;border: solid thin black;">' . "\n";
$output_end_block = '</tr>' . "\n" . '</table>' . "\n\n";
// prepare output based on suitable content components
$output = '<!-- BOF: paypalwpp_admin_notification -->' . "\n";
$output .= '<script title="paypalwpp_admin_notification">
function characterCount(field, count, maxchars) {
  var realchars = field.value.replace(/\t|\r|\n|\r\n/g,\'\');
  var excesschars = realchars.length - maxchars;
  if (excesschars > 0) {
    field.value = field.value.substring(0, maxchars);
    alert("Error!\n\nYou are only allowed to enter up to " + maxchars + " characters.");
  } else {
    count.value = maxchars - realchars.length;
  }
}
</script>' . "\n";
$output .= $output_start_block;
$authcapt_on = isset($_GET['authcapt']) && $_GET['authcapt'] == 'on';
if (isset($response['RESPMSG'])) {
    // payflow
    $output .= $output_p_fmain;
    if (method_exists($this, '_doVoid') && (MODULE_PAYMENT_PAYPALDP_TRANSACTION_MODE == 'Auth Only' || defined('MODULE_PAYMENT_PAYFLOW_TRANSACTION_MODE') && MODULE_PAYMENT_PAYFLOW_TRANSACTION_MODE == 'Auth Only' || $authcapt_on)) {
        $output .= $output_void;
    }
    if (method_exists($this, '_doCapt') && (MODULE_PAYMENT_PAYPALDP_TRANSACTION_MODE == 'Auth Only' || defined('MODULE_PAYMENT_PAYFLOW_TRANSACTION_MODE') && MODULE_PAYMENT_PAYFLOW_TRANSACTION_MODE == 'Auth Only' || $authcapt_on)) {
        $output .= $output_capt;
    }
    if (method_exists($this, '_doRefund')) {
        $output .= $output_refund;
    }
} else {
    // PayPal
    $output .= $output_pay_pal;
    // one table row, four cells, one table in each
    $output .= $output_end_block;
    // close first table
    if (defined('MODULE_PAYMENT_PAYPALWPP_STATUS') || defined('MODULE_PAYMENT_PAYPALDP_STATUS')) {
        $output .= $output_start_block;
        //start second table
        $transaction_type_authorization = isset($response['TRANSACTION_TYPE']) && $response['TRANSACTION_TYPE'] == 'Authorization';
        $transactiontype_payment = isset($response['TRANSACTIONTYPE']) && in_array($response['TRANSACTIONTYPE'], ['cart', 'expresscheckout', 'webaccept']);
        if ($transaction_type_authorization || $transactiontype_payment && $response['PAYMENTTYPE'] == 'instant' && $response['PENDINGREASON'] == 'authorization' || $authcapt_on) {
            if (method_exists($this, '_doRefund') && ($response['PAYMENTTYPE'] != 'instant' || $module == 'paypaldp')) {
                $output .= $output_refund;
            }
            zen_define_default('MODULE_PAYMENT_PAYPALWPP_TRANSACTION_MODE', '');
            zen_define_default('MODULE_PAYMENT_PAYPALDP_TRANSACTION_MODE', '');
            if (MODULE_PAYMENT_PAYPALWPP_TRANSACTION_MODE === 'Auth Only' || MODULE_PAYMENT_PAYPALDP_TRANSACTION_MODE === 'Auth Only') {
                if (method_exists($this, '_doAuth')) {
                    $output .= $output_auth;
                }
                if (method_exists($this, '_doCapt')) {
                    $output .= $output_capt;
                }
            }
            if (method_exists($this, '_doVoid')) {
                $output .= $output_void;
            }
        } else {
            if (method_exists($this, '_doRefund')) {
                $output .= $output_refund;
            }
            if (method_exists($this, '_doVoid') && isset($response['PAYMENTTYPE']) && $response['PAYMENTTYPE'] == 'instant' && $response['PAYMENTSTATUS'] != 'Voided' && $module != 'paypaldp') {
                $output .= $output_void;
            }
        }
    }
}
$output .= $output_end_block;
//close second table
$output .= '<!-- EOF: paypalwpp_admin_notification -->';