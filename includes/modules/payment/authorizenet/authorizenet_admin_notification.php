<?php

declare (strict_types=1);
/**
 * authorizenet_admin_notification.php admin display component
 *
 * @copyright Copyright 2003-2022 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2020 Dec 25 Modified in v1.5.8-alpha $
 */
$output_start_block = '';
$output_main = '';
$output_auth = '';
$output_capt = '';
$output_void = '';
$output_refund = '';
$output_end_block = '';
$output = '';
$output_start_block .= '<table class="noprint">' . "\n";
$output_start_block .= '<tr style="background-color : #bbbbbb; border-style : dotted;">' . "\n";
$output_end_block .= '</tr>' . "\n";
$output_end_block .= '</table>' . "\n";
if (method_exists($this, '_doRefund')) {
    $output_refund .= '<td><table class="noprint">' . "\n";
    $output_refund .= '<tr style="background-color : #dddddd; border-style : dotted;">' . "\n";
    $output_refund .= '<td class="main">' . MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_REFUND_TITLE . '<br>' . "\n";
    $output_refund .= zen_draw_form('aimrefund', FILENAME_ORDERS, zen_get_all_get_params(['action']) . 'action=doRefund', 'post') . zen_hide_session_id();
    $output_refund .= MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_REFUND . '<br>';
    $output_refund .= MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_REFUND_AMOUNT_TEXT . ' ' . zen_draw_input_field('refamt', 'enter amount', 'length="8"') . '<br>';
    $output_refund .= MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_REFUND_CC_NUM_TEXT . ' ' . zen_draw_input_field('cc_number', 'last 4 digits', 'length="20"') . '<br>';
    //trans ID field
    $output_refund .= MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_REFUND_TRANS_ID . ' ' . zen_draw_input_field('trans_id', 'transaction #', 'length="20"') . '<br>';
    // confirm checkbox
    $output_refund .= MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_REFUND_CONFIRM_CHECK . zen_draw_checkbox_field('refconfirm', '', false) . '<br>';
    //comment field
    $output_refund .= '<br>' . MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_REFUND_TEXT_COMMENTS . '<br>' . zen_draw_textarea_field('refnote', 'soft', '50', '3', MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_REFUND_DEFAULT_MESSAGE);
    //message text
    $output_refund .= '<br>' . MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_REFUND_SUFFIX;
    $output_refund .= '<br><input type="submit" name="buttonrefund" value="' . MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_REFUND_BUTTON_TEXT . '" title="' . MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_REFUND_BUTTON_TEXT . '">';
    $output_refund .= '</form>';
    $output_refund .= '</td></tr></table></td>' . "\n";
}
if (method_exists($this, '_doCapt')) {
    $output_capt .= '<td valign="top"><table class="noprint">' . "\n";
    $output_capt .= '<tr style="background-color : #dddddd; border-style : dotted;">' . "\n";
    $output_capt .= '<td class="main">' . MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_CAPTURE_TITLE . '<br>' . "\n";
    $output_capt .= zen_draw_form('aimcapture', FILENAME_ORDERS, zen_get_all_get_params(['action']) . 'action=doCapture', 'post') . zen_hide_session_id();
    $output_capt .= MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_CAPTURE . '<br>';
    $output_capt .= '<br>' . MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_CAPTURE_AMOUNT_TEXT . ' ' . zen_draw_input_field('captamt', 'enter amount', 'length="8"') . '<br>';
    $output_capt .= MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_CAPTURE_TRANS_ID . '<br>' . zen_draw_input_field('captauthid', 'enter auth ID', 'length="32"') . '<br>';
    // confirm checkbox
    $output_capt .= MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_CAPTURE_CONFIRM_CHECK . zen_draw_checkbox_field('captconfirm', '', false) . '<br>';
    //comment field
    $output_capt .= '<br>' . MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_CAPTURE_TEXT_COMMENTS . '<br>' . zen_draw_textarea_field('captnote', 'soft', '50', '2', MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_CAPTURE_DEFAULT_MESSAGE);
    //message text
    $output_capt .= '<br>' . MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_CAPTURE_SUFFIX;
    $output_capt .= '<br><input type="submit" name="btndocapture" value="' . MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_CAPTURE_BUTTON_TEXT . '" title="' . MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_CAPTURE_BUTTON_TEXT . '">';
    $output_capt .= '</form>';
    $output_capt .= '</td></tr></table></td>' . "\n";
}
if (method_exists($this, '_doVoid')) {
    $output_void .= '<td valign="top"><table class="noprint">' . "\n";
    $output_void .= '<tr style="background-color : #dddddd; border-style : dotted;">' . "\n";
    $output_void .= '<td class="main">' . MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_VOID_TITLE . '<br>' . "\n";
    $output_void .= zen_draw_form('aimvoid', FILENAME_ORDERS, zen_get_all_get_params(['action']) . 'action=doVoid', 'post') . zen_hide_session_id();
    $output_void .= MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_VOID . '<br>' . zen_draw_input_field('voidauthid', 'enter auth/trans ID', 'length="32"');
    $output_void .= '<br>' . MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_VOID_CONFIRM_CHECK . zen_draw_checkbox_field('voidconfirm', '', false);
    //comment field
    $output_void .= '<br><br>' . MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_VOID_TEXT_COMMENTS . '<br>' . zen_draw_textarea_field('voidnote', 'soft', '50', '3', MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_VOID_DEFAULT_MESSAGE);
    //message text
    $output_void .= '<br>' . MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_VOID_SUFFIX;
    // confirm checkbox
    $output_void .= '<br><input type="submit" name="ordervoid" value="' . MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_VOID_BUTTON_TEXT . '" title="' . MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_VOID_BUTTON_TEXT . '">';
    $output_void .= '</form>';
    $output_void .= '</td></tr></table></td>' . "\n";
}
// prepare output based on suitable content components
if (defined('MODULE_PAYMENT_AUTHORIZENET_AIM_STATUS') && MODULE_PAYMENT_AUTHORIZENET_AIM_STATUS != '') {
    $output = '<!-- BOF: aim admin transaction processing tools -->';
    $output .= $output_start_block;
    if (MODULE_PAYMENT_AUTHORIZENET_AIM_AUTHORIZATION_TYPE == 'Authorize' || isset($_GET['authcapt']) && $_GET['authcapt'] == 'on') {
        if (method_exists($this, '_doRefund')) {
            $output .= $output_refund;
        }
        if (method_exists($this, '_doCapt')) {
            $output .= $output_capt;
        }
        if (method_exists($this, '_doVoid')) {
            $output .= $output_void;
        }
    } else {
        if (method_exists($this, '_doRefund')) {
            $output .= $output_refund;
        }
        if (method_exists($this, '_doVoid')) {
            $output .= $output_void;
        }
    }
    $output .= $output_end_block;
    $output .= '<!-- EOF: aim admin transaction processing tools -->';
}