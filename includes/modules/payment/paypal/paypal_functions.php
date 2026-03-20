<?php

declare (strict_types=1);
/**
 * functions used by payment module class for Paypal IPN payment method
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @copyright Portions Copyright 2004 DevosC.com
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 30 Modified in v2.2.0 $
 */
// Functions for paypal processing
if (!function_exists('datetime_to_sql_format')) {
    /**
     * Used especially for converting PayPal-IPN dates to a standard format for db storage
     * @since ZC v1.2.4
     */
    function datetime_to_sql_format(string $date_string, string $format = 'H:i:s M d, Y e'): string
    {
        $date_time = DateTime::create_from_format($format, $date_string);
        $date_time->set_timezone((new DateTime())->get_timezone());
        return $date_time->format('Y-m-d H:i:s');
    }
}
if (!function_exists('convertToLocalTimeZone')) {
    /** Used primarily to convert a time value from one timezone to another
     *  particularly when no timezone component is included in the time value.
     *  Mainly needed for converting 3rd party Zulu time values to local time
     * @since ZC v2.0.0
     */
    function convert_to_local_time_zone(string $date_time, string $from_tz = 'UTC', string $output_format = 'Y-m-d H:i:s'): string
    {
        $local_date_time = new DateTime($date_time, new DateTimeZone($from_tz));
        $local_date_time->set_timezone((new DateTime())->get_timezone());
        return $local_date_time->format($output_format);
    }
}
/**
 * @since ZC v1.3.0
 */
function ipn_debug_email($message, $email_address = '', $always_send = false, string $subjecttext = 'IPN DEBUG message'): string
{
    static $paypal_error_counter;
    static $paypal_instance_id;
    $logfile = '';
    if ($email_address == '') {
        $email_address = defined('MODULE_PAYMENT_PAYPAL_DEBUG_EMAIL_ADDRESS') ? MODULE_PAYMENT_PAYPAL_DEBUG_EMAIL_ADDRESS : STORE_OWNER_EMAIL_ADDRESS;
    }
    if (!isset($paypal_error_counter)) {
        $paypal_error_counter = 0;
    }
    if (!isset($paypal_instance_id)) {
        $paypal_instance_id = time() . '_' . zen_create_random_value(4);
    }
    if (defined('MODULE_PAYMENT_PAYPALWPP_DEBUGGING') && MODULE_PAYMENT_PAYPALWPP_DEBUGGING == 'Log and Email' || defined('MODULE_PAYMENT_PAYPAL_IPN_DEBUG') && MODULE_PAYMENT_PAYPAL_IPN_DEBUG == 'Log and Email' || $always_send) {
        $paypal_error_counter++;
        zen_mail(STORE_OWNER, $email_address, $subjecttext . ' (' . $paypal_instance_id . ') #' . $paypal_error_counter, $message, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, ['EMAIL_MESSAGE_HTML' => $message], 'debug');
    }
    if (defined('MODULE_PAYMENT_PAYPAL_IPN_DEBUG') && (MODULE_PAYMENT_PAYPAL_IPN_DEBUG == 'Log and Email' || MODULE_PAYMENT_PAYPAL_IPN_DEBUG == 'Log File' || MODULE_PAYMENT_PAYPAL_IPN_DEBUG == 'Yes') || defined('MODULE_PAYMENT_PAYPALWPP_DEBUGGING') && (MODULE_PAYMENT_PAYPALWPP_DEBUGGING == 'Log File' || MODULE_PAYMENT_PAYPALWPP_DEBUGGING == 'Log and Email')) {
        return ipn_add_error_log($message, $paypal_instance_id);
    }
    return $logfile;
}
/**
 * @since ZC v1.3.0
 */
function ipn_get_stored_session($session_stuff): bool
{
    global $db;
    if (!is_array($session_stuff) || !isset($session_stuff[1])) {
        ipn_debug_email('IPN FATAL ERROR :: Could not find Zen Cart custom variable in POST, cannot validate or re-create session as a transaction initiated from this store. Might be from another source such as eBay or another PayPal store using this PayPal account.');
        return false;
    }
    $sql = 'SELECT *
            FROM ' . TABLE_PAYPAL_SESSION . '
            WHERE session_id = :sessionID';
    $sql = $db->bind_vars($sql, ':sessionID', $session_stuff[1], 'string');
    $stored_session = $db->Execute($sql);
    if ($stored_session->record_count() < 1) {
        global $is_e_ctransaction, $is_d_ptransaction;
        if (isset($_POST['payment_type']) && $_POST['payment_type'] == 'instant' && $is_d_ptransaction && (isset($_POST['auth_status']) && $_POST['auth_status'] == 'Completed' || $_POST['payment_status'] == 'Completed')) {
            $session_stuff[1] = '(EC/DP transaction)';
        }
        ipn_debug_email('IPN ERROR :: Could not find stored session {' . $session_stuff[1] . '} in DB; thus cannot validate or re-create session as a transaction awaiting PayPal Website Payments Standard confirmation initiated by this store. Might be an Express Checkout or eBay transaction or some other action that triggers PayPal IPN notifications.');
        return false;
    }
    $_SESSION = unserialize(base64_decode((string) $stored_session->fields['saved_session']), ['allowed_classes' => false]);
    return true;
}
/**
 * look up parent/original transaction record data and return matching order info if found, along with txn_type
 * @since ZC v1.3.7
 */
function ipn_lookup_transaction(array $post_array): array
{
    global $db;
    // find Zen Cart order number from the transactionID in the IPN
    $orders_id = 0;
    $paypalipn_id = 0;
    $trans_type = 'unknown';
    $sql = 'SELECT order_id, paypal_ipn_id, payment_status, txn_type, pending_reason
                FROM ' . TABLE_PAYPAL . '
                WHERE txn_id = :transactionID: OR invoice = :transactionID:
                ORDER BY order_id DESC LIMIT 1 ';
    if (isset($post_array['parent_txn_id']) && trim($post_array['parent_txn_id']) != '') {
        $sql_parent = $db->bind_vars($sql, ':transactionID:', $post_array['parent_txn_id'], 'string');
        $ipn_id = $db->Execute($sql_parent);
        if ($ipn_id->record_count() > 0) {
            ipn_debug_email('IPN NOTICE :: This transaction HAS a parent record. Thus this is an update of some sort.');
            $trans_type = $ipn_id->fields['pending_reason'] == 'paymentreview' ? 'reviewed' : 'parent';
            $orders_id = $ipn_id->fields['order_id'];
            $paypalipn_id = $ipn_id->fields['paypal_ipn_id'];
        }
    } else {
        $sql_txn = $db->bind_vars($sql, ':transactionID:', $post_array['txn_id'], 'string');
        $ipn_id = $db->Execute($sql_txn);
        if ($ipn_id->record_count() <= 0) {
            ipn_debug_email('IPN NOTICE :: Could not find matched txn_id record in DB. Therefore is new to us. ');
            $trans_type = 'unique';
        } else {
            while (!$ipn_id->EOF) {
                switch ($ipn_id->fields['pending_reason']) {
                    case 'address':
                        ipn_debug_email('IPN NOTICE :: Found pending-address record in database');
                        if ($post_array['payment_status'] == 'Completed') {
                            $trans_type = 'cleared-address';
                        }
                        if ($post_array['payment_status'] == 'Denied') {
                            $trans_type = 'denied-address';
                        }
                        if ($post_array['payment_status'] == 'Pending') {
                            $trans_type = 'pending-address';
                        }
                        break;
                    case 'multi_currency':
                        ipn_debug_email('IPN NOTICE :: Found pending-multicurrency record in database');
                        if ($post_array['payment_status'] == 'Completed') {
                            $trans_type = 'cleared-multicurrency';
                        }
                        if ($post_array['payment_status'] == 'Denied') {
                            $trans_type = 'denied-multicurrency';
                        }
                        if ($post_array['payment_status'] == 'Pending') {
                            $trans_type = 'pending-multicurrency';
                        }
                        break;
                    case 'echeck':
                        ipn_debug_email('IPN NOTICE :: Found pending-echeck record in database');
                        if ($post_array['payment_status'] == 'Completed') {
                            $trans_type = 'cleared-echeck';
                        }
                        if ($post_array['payment_status'] == 'Completed' && $post_array['txn_type'] == 'web_accept') {
                            $trans_type = 'cleared-echeck';
                        }
                        if ($post_array['payment_status'] == 'Denied') {
                            $trans_type = 'denied-echeck';
                        }
                        if ($post_array['payment_status'] == 'Failed') {
                            $trans_type = 'failed-echeck';
                        }
                        if ($post_array['payment_status'] == 'Pending') {
                            $trans_type = 'pending-echeck';
                        }
                        break;
                    case 'authorization':
                        ipn_debug_email('IPN NOTICE :: Found pending-authorization record in database');
                        $trans_type = 'cleared-authorization';
                        if ($post_array['payment_status'] == 'Voided') {
                            $trans_type = 'voided';
                        }
                        if ($post_array['payment_status'] == 'Pending') {
                            $trans_type = 'pending-authorization';
                        }
                        if ($post_array['payment_status'] == 'Captured') {
                            $trans_type = 'captured';
                        }
                        if ($post_array['payment_status'] == 'Completed') {
                            $trans_type = 'cleared-authorization';
                        }
                        if ($post_array['auth_status'] == 'In_Progress') {
                            $trans_type = 'partial-authorization';
                        }
                        break;
                    case 'verify':
                        ipn_debug_email('IPN NOTICE :: Found pending-verify record in database');
                        $trans_type = 'cleared-verify';
                        break;
                    case 'paymentreview':
                        ipn_debug_email('IPN NOTICE :: Found pending-review record in database');
                        $trans_type = 'pending-paymentreview';
                        if ($post_array['payment_status'] == 'Completed') {
                            $trans_type = 'cleared-review';
                        }
                        break;
                    case 'intl':
                        ipn_debug_email('IPN NOTICE :: Found pending-intl record in database');
                        if ($post_array['payment_status'] == 'Completed') {
                            $trans_type = 'cleared-intl';
                        }
                        if ($post_array['payment_status'] == 'Denied') {
                            $trans_type = 'denied-intl';
                        }
                        if ($post_array['payment_status'] == 'Pending') {
                            $trans_type = 'pending-intl';
                        }
                        break;
                    case 'unilateral':
                        ipn_debug_email('IPN NOTICE :: Found record in database.' . "\n" . '*** NOTE: TRANSACTION IS IN *unilateral* STATUS pending creation of a PayPal account for this receiver_email address.' . "\n" . 'Please create the account, or make sure the account is *Verified*.');
                        $trans_type = 'pending-unilateral';
                        break;
                }
                if ($trans_type != 'unknown') {
                    $orders_id = $ipn_id->fields['order_id'];
                    $paypalipn_id = $ipn_id->fields['paypal_ipn_id'];
                }
                $ipn_id->move_next();
            }
        }
    }
    return ['order_id' => $orders_id, 'paypal_ipn_id' => $paypalipn_id, 'txn_type' => $trans_type];
}
/**
 * IPN Validation
 * - match email addresses
 * - ensure that "VERIFIED" has been returned (otherwise somebody is trying to spoof)
 * @since ZC v1.3.0
 */
function ipn_validate_transaction(string $info, array $post_array, $mode = 'IPN'): bool
{
    if ($mode == 'IPN' && !preg_match('/VERIFIED/i', $info) && !preg_match('/SUCCESS/i', $info)) {
        ipn_debug_email('IPN WARNING :: Transaction was NOT marked as VERIFIED. Keep this report for potential use in fraud investigations.' . "\n" . 'IPN Info: ' . "\n" . $info);
        return false;
    }
    if ($mode == 'PDT' && (!preg_match('/SUCCESS/i', $info) || preg_match('/FAIL/i', $info))) {
        ipn_debug_email('IPN WARNING :: PDT Transaction was NOT marked as SUCCESS. Keep this report for potential use in fraud investigations.' . "\n" . 'IPN Info: ' . "\n" . $info);
        return false;
    }
    $pp_bus_email = false;
    $pp_rec_email = false;
    if (defined('MODULE_PAYMENT_PAYPAL_BUSINESS_ID')) {
        if (strtolower(trim((string) $post_array['business'])) == strtolower(trim((string) MODULE_PAYMENT_PAYPAL_BUSINESS_ID))) {
            $pp_bus_email = true;
        }
        if (strtolower(trim((string) $post_array['receiver_email'])) == strtolower(trim((string) MODULE_PAYMENT_PAYPAL_BUSINESS_ID))) {
            $pp_rec_email = true;
        }
        if (!$pp_bus_email && !$pp_rec_email) {
            ipn_debug_email('IPN WARNING :: Transaction email address NOT matched.' . "\n" . 'From IPN = ' . $post_array['business'] . ' | ' . $post_array['receiver_email'] . "\n" . 'From CONFIG = ' . MODULE_PAYMENT_PAYPAL_BUSINESS_ID);
            return false;
        }
        ipn_debug_email('IPN INFO :: Transaction email details.' . "\n" . 'From IPN = ' . $post_array['business'] . ' | ' . $post_array['receiver_email'] . "\n" . 'From CONFIG = ' . MODULE_PAYMENT_PAYPAL_BUSINESS_ID);
    }
    return true;
}
// determine acceptable currencies
/**
 * @since ZC v1.3.7.1
 */
function select_pp_currency()
{
    if (!defined('MODULE_PAYMENT_PAYPAL_CURRENCY') || MODULE_PAYMENT_PAYPAL_CURRENCY == 'Selected Currency') {
        $my_currency = $_SESSION['currency'];
    } else {
        $my_currency = substr((string) MODULE_PAYMENT_PAYPAL_CURRENCY, 5);
    }
    $pp_currencies = ['CAD', 'EUR', 'GBP', 'JPY', 'USD', 'AUD', 'CHF', 'CZK', 'DKK', 'HKD', 'HUF', 'NOK', 'NZD', 'PLN', 'SEK', 'SGD', 'THB', 'MXN', 'ILS', 'PHP', 'TWD', 'BRL', 'MYR', 'INR'];
    if (!in_array($my_currency, $pp_currencies)) {
        return 'USD';
    }
    return $my_currency;
}
/**
 * @since ZC v1.3.0
 */
function valid_payment(string $amount, string $currency, $mode = 'IPN'): bool
{
    global $currencies;
    $my_currency = select_pp_currency();
    $exchanged_amount = $mode == 'IPN' ? $amount * $currencies->get_value($my_currency) : $amount;
    $transaction_amount = preg_replace('/[^0-9.]/', '', number_format($exchanged_amount, $currencies->get_decimal_places($my_currency), '.', ''));
    if ($_POST['mc_currency'] != $my_currency || $_POST['mc_gross'] != $transaction_amount && $_POST['mc_gross'] != -0.01 && (!defined('MODULE_PAYMENT_PAYPAL_TESTING') || MODULE_PAYMENT_PAYPAL_TESTING != 'Test')) {
        ipn_debug_email('IPN WARNING :: Currency/Amount Mismatch.  Details: ' . "\n" . 'PayPal email address = ' . $_POST['business'] . "\n" . ' | mc_currency = ' . $_POST['mc_currency'] . "\n" . ' | submitted_currency = ' . $my_currency . "\n" . ' | order_currency = ' . $currency . "\n" . ' | mc_gross = ' . $_POST['mc_gross'] . "\n" . ' | converted_amount = ' . $transaction_amount . "\n" . ' | order_amount = ' . $amount);
        return false;
    }
    ipn_debug_email('IPN INFO :: Currency/Amount Details: ' . "\n" . 'PayPal email address = ' . $_POST['business'] . "\n" . ' | mc_currency = ' . $_POST['mc_currency'] . "\n" . ' | submitted_currency = ' . $my_currency . "\n" . ' | order_currency = ' . $currency . "\n" . ' | mc_gross = ' . $_POST['mc_gross'] . "\n" . ' | converted_amount = ' . $transaction_amount . "\n" . ' | order_amount = ' . $amount);
    return true;
}
/**
 *  is this an existing transaction?
 *    (1) we find a matching record in the "paypal" table
 *    (2) we check for valid txn_types or payment_status such as Denied, Refunded, Partially-Refunded, Reversed, Voided, Expired
 * @since ZC v1.3.7
 */
function ipn_determine_txn_type(array $post_array, $txn_type = 'unknown')
{
    global $parent_lookup;
    if (str_starts_with((string) $txn_type, 'cleared-')) {
        return $txn_type;
    }
    if (isset($post_array['txn_type'])) {
        if ($post_array['txn_type'] === 'send_money') {
            return 'send_money';
        }
        if ($post_array['txn_type'] === 'express_checkout' || $post_array['txn_type'] === 'cart') {
            $txn_type = $post_array['txn_type'];
        }
    }
    // if it's not unique or linked to a parent, then:
    // 1. could be an e-check denied / cleared
    // 2. could be an express-checkout "pending" transaction which has been Accepted in the merchant's PayPal console and needs activation in Zen Cart
    $payment_status = $post_array['payment_status'] ?? '';
    $payment_type = $post_array['payment_type'] ?? '';
    $pending_reason = $post_array['pending_reason'] ?? '';
    switch (true) {
        case $payment_status === 'Completed' && $payment_type === 'echeck':
            $txn_type = $txn_type === 'express-checkout' ? 'express-checkout-cleared' : 'echeck-cleared';
            break;
        case $payment_status === 'Denied':
            $txn_type = $payment_status === 'Failed' && $payment_type === 'echeck' ? 'echeck-denied' : 'denied';
            break;
        case $payment_status === 'Pending' && $pending_reason === 'echeck':
            $txn_type = 'pending-echeck';
            break;
        case $payment_status === 'Pending' && $pending_reason === 'address':
            $txn_type = 'pending-address';
            break;
        case $payment_status === 'Pending' && $pending_reason === 'intl':
            $txn_type = 'pending-intl';
            break;
        case $payment_status === 'Pending' && $pending_reason === 'multi_currency':
            $txn_type = 'pending-multicurrency';
            break;
        case $payment_status === 'Pending' && $pending_reason === 'paymentreview':
            $txn_type = 'pending-paymentreview';
            break;
        case $payment_status === 'Pending' && $pending_reason === 'verify':
            $txn_type = 'pending-verify';
            break;
        case $parent_lookup === 'parent' && $payment_status === 'Completed' && $payment_type === 'instant':
            $txn_type = 'cleared-authorization';
            break;
        case $payment_status === 'Voided' && $payment_type === 'instant':
            $txn_type = 'voided';
            break;
        default:
            break;
    }
    return $txn_type;
}
/**
 * Create order record from IPN data
 * @since ZC v1.3.0
 * @return mixed[]
 */
function ipn_create_order_array($new_order_id, $txn_type): array
{
    // -----
    // First, set elements of the to-be-returned array that are *always* present.
    //
    $sql_data_array = ['order_id' => $new_order_id, 'txn_type' => $txn_type, 'module_name' => 'paypal (ipn-handler)', 'module_mode' => 'IPN', 'payment_date' => datetime_to_sql_format($_POST['payment_date']), 'num_cart_items' => (int) $_POST['num_cart_items'], 'date_added' => 'now()', 'memo' => '{Record generated by IPN}'];
    // -----
    // Next, for each of the other posted values, let them go to the database default if they're
    // not set, noting that *some* of the values have been preset by ipn_main_handler.php.
    //
    $post_varnames = ['reason_code', 'payment_type', 'payment_status', 'pending_reason', 'invoice', 'mc_currency', 'first_name', 'last_name', 'payer_business_name', 'address_name', 'address_street', 'address_city', 'address_state', 'address_zip', 'address_country', 'address_status', 'payer_email', 'payer_id', 'payer_status', 'business', 'receiver_email', 'receiver_id', 'txn_id', 'parent_txn_id', 'mc_gross', 'mc_fee', 'settle_amount', 'settle_currency', 'exchange_rate', 'notify_version', 'verify_sign'];
    foreach ($post_varnames as $varname) {
        if (isset($_POST[$varname])) {
            $sql_data_array[$varname] = $_POST[$varname];
        }
    }
    if (isset($_POST['protection_eligibility']) && $_POST['protection_eligibility'] !== '') {
        $sql_data_array['memo'] .= ' [ProtectionEligibility:' . $_POST['protection_eligibility'] . ']';
    }
    if (isset($_POST['memo']) && $_POST['memo'] !== '') {
        $sql_data_array['memo'] .= ' [Customer Comments:' . $_POST['memo'] . ']';
    }
    return $sql_data_array;
}
/**
 * Create order-history record from IPN data
 * @since ZC v1.3.0
 */
function ipn_create_order_history_array($insert_id): array
{
    return ['paypal_ipn_id' => (int) $insert_id, 'txn_id' => $_POST['txn_id'], 'parent_txn_id' => $_POST['parent_txn_id'], 'payment_status' => $_POST['payment_status'], 'pending_reason' => $_POST['pending_reason'] ?? '', 'date_added' => 'now()'];
}
/**
 * Create order-update from IPN data
 * @since ZC v1.3.0
 * @return mixed[]
 */
function ipn_create_order_update_array($txn_type): array
{
    $sql_data_array = ['payment_type' => $_POST['payment_type'], 'txn_type' => $txn_type, 'parent_txn_id' => $_POST['parent_txn_id'], 'payment_status' => $_POST['payment_status'], 'pending_reason' => $_POST['pending_reason'], 'payer_email' => $_POST['payer_email'], 'payer_id' => $_POST['payer_id'], 'business' => $_POST['business'], 'receiver_email' => $_POST['receiver_email'], 'receiver_id' => $_POST['receiver_id'], 'notify_version' => $_POST['notify_version'], 'verify_sign' => $_POST['verify_sign'], 'last_modified' => 'now()'];
    if (isset($_POST['address_street']) && $_POST['address_street'] != '') {
        $sql_data_array = array_merge($sql_data_array, ['address_name' => $_POST['address_name'], 'address_street' => $_POST['address_street'], 'address_city' => $_POST['address_city'], 'address_state' => $_POST['address_state'], 'address_zip' => $_POST['address_zip'], 'address_country' => $_POST['address_country']]);
    }
    if (isset($_POST['payer_business_name']) && $_POST['payer_business_name'] != '') {
        $sql_data_array['payer_business_name'] = $_POST['payer_business_name'];
    }
    if (isset($_POST['reason_code']) && $_POST['reason_code'] != '') {
        $sql_data_array['reason_code'] = $_POST['reason_code'];
    }
    if (isset($_POST['invoice']) && $_POST['invoice'] != '') {
        $sql_data_array['invoice'] = $_POST['invoice'];
    }
    if (isset($_POST['mc_gross']) && $_POST['mc_gross'] > 0) {
        $sql_data_array['mc_gross'] = $_POST['mc_gross'];
    }
    if (isset($_POST['mc_fee']) && $_POST['mc_fee'] > 0) {
        $sql_data_array['mc_fee'] = $_POST['mc_fee'];
    }
    if (isset($_POST['settle_amount']) && $_POST['settle_amount'] > 0) {
        $sql_data_array['settle_amount'] = $_POST['settle_amount'];
    }
    if (isset($_POST['first_name']) && $_POST['first_name'] != '') {
        $sql_data_array['first_name'] = $_POST['first_name'];
    }
    if (isset($_POST['last_name']) && $_POST['last_name'] != '') {
        $sql_data_array['last_name'] = $_POST['last_name'];
    }
    if (isset($_POST['mc_currency']) && $_POST['mc_currency'] != '') {
        $sql_data_array['mc_currency'] = $_POST['mc_currency'];
    }
    if (isset($_POST['settle_currency']) && $_POST['settle_currency'] != '') {
        $sql_data_array['settle_currency'] = $_POST['settle_currency'];
    }
    if (isset($_POST['num_cart_items']) && $_POST['num_cart_items'] > 0) {
        $sql_data_array['num_cart_items'] = $_POST['num_cart_items'];
    }
    if (isset($_POST['exchange_rate']) && $_POST['exchange_rate'] > 0) {
        $sql_data_array['exchange_rate'] = $_POST['exchange_rate'];
    }
    $sql_data_array['memo'] = '{Record generated by IPN}';
    if (isset($_POST['protection_eligibility']) && $_POST['protection_eligibility'] != '') {
        $sql_data_array['memo'] .= ' [ProtectionEligibility:' . $_POST['protection_eligibility'] . ']';
    }
    if (isset($_POST['memo']) && $_POST['memo'] != '') {
        $sql_data_array['memo'] .= ' [Customer Comments:' . $_POST['memo'] . ']';
    }
    return $sql_data_array;
}
/**
 * Debug to file
 * @since ZC v1.3.0
 */
function ipn_fopen($filename): string|false
{
    $response = '';
    $fp = @fopen($filename, 'rb');
    if ($fp) {
        $response = get_request_body_contents($fp);
        fclose($fp);
    }
    return $response;
}
/**
 * @since ZC v1.3.0
 */
function get_request_body_contents(&$handle): string|false
{
    if ($handle) {
        $line = '';
        while (!feof($handle)) {
            $line .= @fgets($handle, 1024);
        }
        return $line;
    }
    return false;
}
/**
 * Verify IPN by sending it back to PayPal for confirmation
 * @since ZC v1.3.7
 */
function ipn_postback($mode = 'IPN', $pdt_tx = ''): false|string|array
{
    $postdata = '';
    $postback = '';
    $postback_array = [];
    //build postback string
    if ($mode == 'PDT') {
        if ($pdt_tx == '') {
            return false;
        }
        // TX value not supplied, therefore PDT is disabled on merchant's PayPal profile.
        ipn_debug_email('PDT PROCESSING INITIATED.' . "\n" . 'Preparing to verify transaction via PDT.' . "\n\n" . 'The TX token for verification is: ' . print_r($_GET, true));
        $postback .= 'cmd=_notify-synch';
        $postback .= '&tx=' . $_GET['tx'];
        $postback .= '&at=' . trim(MODULE_PAYMENT_PAYPAL_PDTTOKEN);
        $postback .= '&';
        $postback_array['cmd'] = '_notify-sync';
        $postback_array['tx'] = $_GET['tx'];
        $postback_array['at'] = substr(MODULE_PAYMENT_PAYPAL_PDTTOKEN, 0, 5) . '**********' . substr(MODULE_PAYMENT_PAYPAL_PDTTOKEN, -5);
    } elseif ($mode == 'IPN') {
        $postback .= 'cmd=_notify-validate';
        $postback .= '&';
        $postback_array['cmd'] = '_notify-validate';
    }
    foreach ($_POST as $key => $value) {
        $postdata .= $key . '=' . urlencode(stripslashes((string) $value)) . '&';
        $postback .= $key . '=' . urlencode(stripslashes((string) $value)) . '&';
        $postback_array[$key] = $value;
    }
    if (str_ends_with($postdata, '=&')) {
        ipn_debug_email('IPN NOTICE :: No POST data to process -- Bad IPN data');
        return $postdata;
    }
    $postback = rtrim($postback, '&');
    $postdata_array = $_POST;
    ksort($postdata_array);
    if ($mode == 'IPN') {
        ipn_debug_email('IPN INFO - POST VARS received (sorted):' . "\n" . stripslashes(urldecode(print_r($postdata_array, true))));
        if (sizeof($postdata_array) == 0) {
            die('Nothing to process. Please return to home page.');
        }
    }
    // send received data back to PayPal for validation
    $scheme = 'https://';
    //Parse url
    $web = parse_url($scheme . 'ipnpb.paypal.com/cgi-bin/webscr');
    if (isset($_POST['test_ipn']) && $_POST['test_ipn'] == 1 || defined('MODULE_PAYMENT_PAYPAL_HANDLER') && MODULE_PAYMENT_PAYPAL_HANDLER == 'sandbox') {
        $web = parse_url($scheme . 'ipnpb.sandbox.paypal.com/cgi-bin/webscr');
    }
    //Set the port number
    if ($web['scheme'] == 'https') {
        $web['port'] = '443';
        $ssl = 'ssl://';
    } else {
        $web['port'] = '80';
        $ssl = '';
    }
    $result = '';
    if (function_exists('curl_init')) {
        $result = do_pay_pal_ipn_curl_postback($web, $postback, $postback_array, $mode);
    }
    if ($mode == 'PDT') {
        $info = $result['info'];
        $result = $result['status'];
    }
    //DEBUG ONLY: ipn_debug_email('After CURL: $result='.$result);
    if (!in_array(trim((string) $result), ['VERIFIED', 'SUCCESS', 'INVALID', 'FAIL'])) {
        ipn_debug_email('IPN NOTICE: Could not get usable response via CURL. Trying fsockopen() as fallback.' . ($result != '' ? ' [' . $result . ']' : ''));
        $result = do_pay_pal_ipn_fsockopen_postback($web, $postback, $postback_array, $ssl, $mode);
        if ($mode == 'PDT') {
            $info = $result['info'];
            $result = $result['status'];
        }
    }
    return $mode == 'PDT' ? ['status' => $result, 'info' => $info] : trim((string) $result);
}
/**
 * @since ZC v1.3.9a
 */
function do_pay_pal_ipn_fsockopen_postback(array $web, string $postback, $postback_array, $ssl, $mode = 'IPN'): array|string
{
    $header = 'POST ' . $web['path'] . " HTTP/1.1\r\n";
    $header .= 'Host: ' . $web['host'] . "\r\n";
    $header .= "Content-type: application/x-www-form-urlencoded\r\n";
    $header .= 'Content-length: ' . strlen($postback) . "\r\n";
    $header .= "Connection: close\r\n\r\n";
    $errnum = 0;
    $errstr = '';
    ipn_debug_email('IPN INFO - POST VARS to be sent back (unsorted) for validation (using fsockopen): ' . "\n" . 'To: ' . $ssl . $web['host'] . ':' . $web['port'] . "\n" . $header . stripslashes(print_r($postback_array, true)));
    //Create paypal connection
    if (defined('MODULE_PAYMENT_PAYPAL_IPN_DEBUG') && MODULE_PAYMENT_PAYPAL_IPN_DEBUG == 'Yes') {
        $fp = fsockopen($ssl . $web['host'], $web['port'], $errnum, $errstr, 30);
    } else {
        $fp = @fsockopen($ssl . $web['host'], $web['port'], $errnum, $errstr, 30);
    }
    if (!$fp && $ssl == 'ssl://') {
        ipn_debug_email('IPN ERROR :: Could not establish fsockopen: ' . "\n" . 'Host Details = ' . $ssl . $web['host'] . ':' . $web['port'] . ' (' . $errnum . ') ' . $errstr . "\n Trying again with HTTPS over 443 ...");
        $ssl = 'https://';
        $web['port'] = '443';
        $fp = @fsockopen($ssl . $web['host'], $web['port'], $errnum, $errstr, 30);
    }
    if (!$fp && $ssl == 'https://') {
        ipn_debug_email('IPN ERROR :: Could not establish fsockopen: ' . "\n" . 'Host Details = ' . $ssl . $web['host'] . ':' . $web['port'] . ' (' . $errnum . ') ' . $errstr . "\n Trying again directly over 443 ...");
        $ssl = '';
        $web['port'] = '443';
        $fp = @fsockopen($ssl . $web['host'], $web['port'], $errnum, $errstr, 30);
    }
    if (!$fp) {
        ipn_debug_email('IPN ERROR :: Could not establish fsockopen: ' . "\n" . 'Host Details = ' . $ssl . $web['host'] . ':' . $web['port'] . ' (' . $errnum . ') ' . $errstr . "\n Trying again with HTTP over port 80 ...");
        $ssl = 'http://';
        $web['port'] = '80';
        $fp = @fsockopen($ssl . $web['host'], $web['port'], $errnum, $errstr, 30);
    }
    if (!$fp) {
        ipn_debug_email('IPN ERROR :: Could not establish fsockopen: ' . "\n" . 'Host Details = ' . $ssl . $web['host'] . ':' . $web['port'] . ' (' . $errnum . ') ' . $errstr . "\n Trying again without any specified protocol, using port 80 ...");
        $ssl = '';
        $web['port'] = '80';
        $fp = @fsockopen($ssl . $web['host'], $web['port'], $errnum, $errstr, 30);
    }
    if (!$fp) {
        ipn_debug_email('IPN FATAL ERROR :: Could not establish fsockopen. ' . "\n" . 'Host Details = ' . $ssl . $web['host'] . ':' . $web['port'] . ' (' . $errnum . ') ' . $errstr . "\nABORTED.");
        die;
    }
    $info = [];
    fputs($fp, $header . $postback . "\r\n\r\n");
    $header_data = '';
    $headerdone = false;
    //loop through the response from the server
    while (!feof($fp)) {
        $line = @fgets($fp, 1024);
        if (strcmp($line, "\r\n") == 0) {
            // this is a header row
            $headerdone = true;
            $header_data .= $line;
        } elseif ($headerdone) {
            // header has been read. now read the contents
            $info[] = $line;
        }
    }
    //close $fp - we are done with it
    fclose($fp);
    //break up results into a string
    $info = implode('', $info);
    $firstline = trim(substr($info, 0, 20));
    $status = '';
    if (str_starts_with($firstline, 'VERIFIED')) {
        $status = 'VERIFIED';
    }
    if ($status == '' && str_starts_with($firstline, 'SUCCESS')) {
        $status = 'SUCCESS';
    }
    if ($status == '' && str_starts_with($firstline, 'FAIL')) {
        $status = 'FAIL';
    }
    if ($status == '' && str_starts_with($firstline, 'INVALID')) {
        $status = 'INVALID';
    }
    if ($status == '' && str_starts_with($firstline, 'UNDETERMINED')) {
        $status = 'UNDETERMINED';
    }
    ipn_debug_email('IPN INFO (fs) - Confirmation/Validation response ' . ($status != '' ? $status : $header_data . $info));
    return $mode == 'PDT' ? ['status' => $status, 'info' => $info] : $status;
}
/**
 * @since ZC v1.3.9a
 */
function do_pay_pal_ipn_curl_postback(array $url, $vars, $vars_array, $mode = 'IPN')
{
    ipn_debug_email('IPN INFO - POST VARS to be sent back (unsorted) for validation (using CURL): ' . "\n" . 'To: ' . $url['host'] . ':' . $url['port'] . "\n" . stripslashes(print_r($vars_array, true)));
    $curl_opts = [
        CURLOPT_URL => 'https://' . $url['host'] . $url['path'],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $vars,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_VERBOSE => false,
        CURLOPT_HEADER => false,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_RETURNTRANSFER => true,
        //CURLOPT_SSL_VERIFYPEER => FALSE, // Leave this line commented out! This should never be set to FALSE on a live site!
        //CURLOPT_CAINFO => '/local/path/to/cacert.pem', // for offline testing, this file can be obtained from http://curl.haxx.se/docs/caextract.html ... should never be used in production!
        CURLOPT_FORBID_REUSE => true,
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_USERAGENT => 'Zen Cart(R) - IPN Postback',
    ];
    if (CURL_PROXY_REQUIRED == 'True') {
        $proxy_tunnel_flag = defined('CURL_PROXY_TUNNEL_FLAG') && strtoupper((string) CURL_PROXY_TUNNEL_FLAG) == 'FALSE' ? false : true;
        $curl_opts[CURLOPT_HTTPPROXYTUNNEL] = $proxy_tunnel_flag;
        $curl_opts[CURLOPT_PROXYTYPE] = CURLPROXY_HTTP;
        $curl_opts[CURLOPT_PROXY] = CURL_PROXY_SERVER_DETAILS;
    }
    $ch = curl_init();
    curl_setopt_array($ch, $curl_opts);
    $response = curl_exec($ch);
    $comm_error = curl_error($ch);
    $comm_err_no = curl_errno($ch);
    if ($comm_err_no == 35) {
        curl_setopt($ch, CURLOPT_SSLVERSION, 6);
        $response = curl_exec($ch);
        $comm_error = curl_error($ch);
        $comm_err_no = curl_errno($ch);
    }
    $comm_info = @curl_getinfo($ch);
    $errors = $comm_err_no != 0 ? 'CURL communication ERROR: (' . $comm_err_no . ') ' . $comm_error : '';
    $response .= $comm_err_no != 0 ? '&CURL_ERRORS=' . urlencode('(' . $comm_err_no . ') ' . $comm_error) : '';
    //    $response .=  ($commErrNo != 0 ? '&CURL_INFO=' . urlencode($commInfo) : '');
    ipn_debug_email('CURL OPTS: ' . print_r($curl_opts, true));
    ipn_debug_email('CURL response: ' . $response);
    if ($errors != '') {
        ipn_debug_email('CURL errors: ' . $errors, print_r($comm_info, true));
    }
    //echo 'INFO: <pre>'; print_r($commInfo); echo '</pre><br>';
    //echo 'ERROR: ' . $errors . '<br>';
    //print_r($response) ;
    if (($response == '' || $errors != '') && $url['scheme'] != 'http') {
        $url['scheme'] = 'http';
        $url['port'] = '80';
        ipn_debug_email('CURL ERROR: ' . $errors . "\n" . 'Trying direct HTTP on port 80 instead ... ' . $url['scheme'] . '://' . $url['host'] . $url['path'] . "\n");
        $ch = curl_init();
        $curl_opts[CURLOPT_URL] = $url['scheme'] . '://' . $url['host'] . $url['path'];
        $curl_opts[CURLOPT_FOLLOWLOCATION] = true;
        // allow to follow redirects since PP usually redirects all non-SSL to SSL etc, do a redirect is almost certain to occur
        curl_setopt_array($ch, $curl_opts);
        curl_setopt($ch, CURLOPT_PORT, $url['port']);
        $response = curl_exec($ch);
        $comm_error = curl_error($ch);
        $comm_err_no = curl_errno($ch);
        $comm_info = @curl_getinfo($ch);
        ipn_debug_email('CURL OPTS: ' . print_r($curl_opts, true));
        ipn_debug_email('CURL response: ' . $response);
        $errors = $comm_err_no != 0 ? "\n(" . $comm_err_no . ') ' . $comm_error : '';
        if ($errors != '') {
            ipn_debug_email(nl2br('CURL ERROR: ' . $errors . "\n" . 'ABORTING CURL METHOD ...' . "\n\n"));
        }
    }
    $firstline = trim(substr($response, 0, 20));
    $status = '';
    if (str_starts_with($firstline, 'VERIFIED')) {
        $status = 'VERIFIED';
    }
    if ($status == '' && str_starts_with($firstline, 'SUCCESS')) {
        $status = 'SUCCESS';
    }
    if ($status == '' && str_starts_with($firstline, 'FAIL')) {
        $status = 'FAIL';
    }
    if ($status == '' && str_starts_with($firstline, 'INVALID')) {
        $status = 'INVALID';
    }
    if ($status == '' && str_starts_with($firstline, 'UNDETERMINED')) {
        $status = 'UNDETERMINED';
    }
    ipn_debug_email('IPN INFO (cl) - Confirmation/Validation response ' . ($status != '' ? $status : $response));
    if ($response != '') {
        return $mode == 'PDT' ? ['status' => $status, 'info' => $response] : $response;
    }
    return $errors;
}
/**
 * Write order-history update to ZC tables denoting the update supplied by the IPN
 * @since ZC v1.3.7
 */
function ipn_update_orders_status_and_history($orders_id, $new_status = 1, string $txn_type = ''): void
{
    global $db;
    ipn_debug_email('IPN NOTICE :: Updating order #' . (int) $orders_id . ' to status: ' . (int) $new_status . ' (txn_type: ' . $txn_type . ')');
    $comments = 'PayPal status: ' . $_POST['payment_status'] . ' ' . ' @ ' . $_POST['payment_date'] . ($_POST['parent_txn_id'] != '' ? "\n" . ' Parent Trans ID:' . $_POST['parent_txn_id'] : '') . "\n" . ' Trans ID:' . $_POST['txn_id'] . "\n" . ' Amount: ' . $_POST['mc_gross'] . ' ' . $_POST['mc_currency'];
    zen_update_orders_history($orders_id, $comments, null, $new_status, 0);
    ipn_debug_email('IPN NOTICE :: Update complete.');
    /**
     * Activate any downloads associated with an order which has now been cleared
     */
    if ($txn_type == 'echeck-cleared' || $txn_type == 'express-checkout-cleared' || str_starts_with($txn_type, 'cleared-')) {
        $check_status = $db->Execute('SELECT date_purchased FROM ' . TABLE_ORDERS . " WHERE orders_id = '" . (int) $orders_id . "'");
        $zc_max_days = zen_date_diff($check_status->fields['date_purchased'], date('Y-m-d H:i:s', time())) + (int) DOWNLOAD_MAX_DAYS;
        ipn_debug_email('IPN NOTICE :: Updating order #' . (int) $orders_id . ' downloads (if any).  New max days: ' . $zc_max_days . ', New count: ' . (int) DOWNLOAD_MAX_COUNT);
        $update_downloads_query = 'UPDATE ' . TABLE_ORDERS_PRODUCTS_DOWNLOAD . " SET download_maxdays='" . $zc_max_days . "', download_count='" . (int) DOWNLOAD_MAX_COUNT . "' WHERE orders_id='" . (int) $orders_id . "'";
        $db->Execute($update_downloads_query);
    }
}
/**
 * Prepare subtotal and line-item detail content to send to PayPal
 * @since ZC v1.3.8
 * @return mixed[]
 */
function ipn_get_line_item_details($restricted_currency): array
{
    global $order, $currencies, $order_totals, $order_total_modules;
    // if not default currency, do not send subtotals or line-item details
    if (DEFAULT_CURRENCY != $order->info['currency'] || $restricted_currency != DEFAULT_CURRENCY) {
        ipn_logging('getLineItemDetails 1', 'Not using default currency. Thus, no line-item details can be submitted.');
        return [];
    }
    if ($currencies->currencies[$_SESSION['currency']]['value'] != 1 || $currencies->currencies[$order->info['currency']]['value'] != 1) {
        ipn_logging('getLineItemDetails 2', 'currency val not equal to 1.0000 - cannot proceed without coping with currency conversions. Aborting line-item details.');
        return [];
    }
    $options_st = [];
    $options_li = [];
    $options_nb = [];
    $number_of_line_items_processed = 0;
    $credits_applied = 0;
    $surcharges = 0;
    $sum_of_line_items = 0;
    $sum_of_line_tax = 0;
    $options_st['amount'] = 0;
    $options_st['subtotal'] = 0;
    $options_st['tax_cart'] = 0;
    $options_st['shipping'] = 0;
    $flag_subtotals_unknown_yet = true;
    $sub_total_li = 0;
    $sub_total_tax = 0;
    $sub_total_shipping = 0;
    $subtotal_pre = ['no data'];
    $discount_problems_flag = false;
    $flag_treat_as_partial = false;
    if (sizeof($order_totals)) {
        // prepare subtotals
        for ($i = 0, $n = sizeof($order_totals); $i < $n; $i++) {
            if ($order_totals[$i]['code'] == '') {
                continue;
            }
            if (in_array($order_totals[$i]['code'], ['ot_total', 'ot_subtotal', 'ot_tax', 'ot_shipping']) || strstr((string) $order_totals[$i]['code'], 'insurance')) {
                if ($order_totals[$i]['code'] == 'ot_shipping') {
                    $options_st['shipping'] = round($order_totals[$i]['value'], 2);
                }
                if ($order_totals[$i]['code'] == 'ot_total') {
                    $options_st['amount'] = round($order_totals[$i]['value'], 2);
                }
                if ($order_totals[$i]['code'] == 'ot_tax') {
                    $options_st['tax_cart'] += round($order_totals[$i]['value'], 2);
                }
                if ($order_totals[$i]['code'] == 'ot_subtotal') {
                    $options_st['subtotal'] = round($order_totals[$i]['value'], 2);
                }
            } else {
                // handle other order totals:
                global ${$order_totals[$i]['code']};
                if (str_starts_with((string) $order_totals[$i]['text'], '-') || isset(${$order_totals[$i]['code']}->credit_class) && ${$order_totals[$i]['code']}->credit_class == true) {
                    // handle credits
                    $credits_applied += round($order_totals[$i]['value'], 2);
                } else {
                    // treat all other OT's as if they're related to handling fees or other extra charges to be added/included
                    $surcharges += $order_totals[$i]['value'];
                }
            }
        }
        if ($credits_applied > 0) {
            $options_st['subtotal'] -= $credits_applied;
        }
        if ($surcharges > 0) {
            $options_st['subtotal'] += $surcharges;
        }
        $options_nb['creditsExist'] = $credits_applied > 0 ? true : false;
        // Handle tax-included scenario
        if (DISPLAY_PRICE_WITH_TAX == 'true') {
            $options_st['tax_cart'] = 0;
        }
        $subtotal_pre = $options_st;
        // Move shipping tax amount from Tax subtotal into Shipping subtotal for submission to PayPal, since PayPal applies tax to each line-item individually
        $module = strpos((string) $_SESSION['shipping']['id'], '_') > 0 ? substr((string) $_SESSION['shipping']['id'], 0, strpos((string) $_SESSION['shipping']['id'], '_')) : $_SESSION['shipping']['id'];
        if (isset($GLOBALS[$module]) && !empty($order->info['shipping_method']) && DISPLAY_PRICE_WITH_TAX != 'true') {
            if ($GLOBALS[$module]->tax_class > 0) {
                $shipping_tax_basis = !isset($GLOBALS[$module]->tax_basis) ? STORE_SHIPPING_TAX_BASIS : $GLOBALS[$module]->tax_basis;
                $shipping_on_billing = zen_get_tax_rate($GLOBALS[$module]->tax_class, $order->billing['country']['id'], $order->billing['zone_id']);
                $shipping_on_delivery = zen_get_tax_rate($GLOBALS[$module]->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
                if ($shipping_tax_basis == 'Billing') {
                    $shipping_tax = $shipping_on_billing;
                } elseif ($shipping_tax_basis == 'Shipping') {
                    $shipping_tax = $shipping_on_delivery;
                } else if (STORE_ZONE == $order->billing['zone_id']) {
                    $shipping_tax = $shipping_on_billing;
                } elseif (STORE_ZONE == $order->delivery['zone_id']) {
                    $shipping_tax = $shipping_on_delivery;
                } else {
                    $shipping_tax = 0;
                }
                $tax_adjustment_for_shipping = zen_round(zen_calculate_tax($order->info['shipping_cost'], $shipping_tax), $currencies->currencies[$_SESSION['currency']]['decimal_places']);
                $options_st['shipping'] += $tax_adjustment_for_shipping;
                $options_st['tax_cart'] -= $tax_adjustment_for_shipping;
            }
        }
        $flag_subtotals_unknown_yet = $options_st['shipping'] + $options_st['amount'] + $options_st['tax_cart'] + $options_st['subtotal'] == 0;
    } else {
        // if we get here, we don't have any order-total information yet because the customer has clicked Express before starting normal checkout flow
        // thus, we must make a note to manually calculate subtotals, rather than relying on the more robust order-total infrastructure
        $flag_subtotals_unknown_yet = true;
    }
    $decimals = $currencies->get_decimal_places($_SESSION['currency']);
    // loop thru all products to prepare details of quantity and price.
    for ($i = 0, $n = sizeof($order->products), $k = 0; $i < $n; $i++) {
        // PayPal is inconsistent in how it handles zero-value line-items, so skip this entry if price is zero
        if ($order->products[$i]['final_price'] == 0) {
            continue;
        }
        $k++;
        $options_li["item_number_{$k}"] = $order->products[$i]['model'];
        $options_li["item_name_{$k}"] = $order->products[$i]['name'] . ' [' . (int) $order->products[$i]['id'] . ']';
        // Append *** if out-of-stock.
        $options_li["item_name_{$k}"] .= zen_get_products_stock($order->products[$i]['id']) - $order->products[$i]['qty'] < 0 ? STOCK_MARK_PRODUCT_OUT_OF_STOCK : '';
        // if there are attributes, loop thru them and add to description
        if (isset($order->products[$i]['attributes']) && sizeof($order->products[$i]['attributes']) > 0) {
            for ($j = 0, $n2 = sizeof($order->products[$i]['attributes']); $j < $n2; $j++) {
                $options_li["item_name_{$k}"] .= "\n " . $order->products[$i]['attributes'][$j]['option'] . ': ' . $order->products[$i]['attributes'][$j]['value'];
            }
            // end loop
        }
        // endif attribute-info
        // PayPal can't handle fractional-quantity values, so convert it to qty 1 here
        if (is_float($order->products[$i]['qty']) && ($order->products[$i]['qty'] != (int) $order->products[$i]['qty'] || $flag_treat_as_partial)) {
            $options_li["item_name_{$k}"] = '(' . $order->products[$i]['qty'] . ' x ) ' . $options_li["item_name_{$k}"];
            // zen_add_tax already handles whether DISPLAY_PRICES_WITH_TAX is set
            $options_li["amount_{$k}"] = zen_round(zen_round(zen_add_tax($order->products[$i]['final_price'], $order->products[$i]['tax']), $decimals) * $order->products[$i]['qty'], $decimals);
            $options_li["quantity_{$k}"] = 1;
            // no line-item tax component
        } else {
            $options_li["quantity_{$k}"] = $order->products[$i]['qty'];
            $options_li["amount_{$k}"] = zen_round(zen_add_tax($order->products[$i]['final_price'], $order->products[$i]['tax']), $decimals);
        }
        $sub_total_li += $options_li["quantity_{$k}"] * $options_li["amount_{$k}"];
        //      $subTotalTax += ($optionsLI["quantity_$k"] * $optionsLI["tax_$k"]);
        // add line-item for one-time charges on this product
        if ($order->products[$i]['onetime_charges'] != 0) {
            $k++;
            $options_li["item_name_{$k}"] = MODULES_PAYMENT_PAYPALSTD_LINEITEM_TEXT_ONETIME_CHARGES_PREFIX . substr(htmlentities((string) $order->products[$i]['name'], ENT_QUOTES, 'UTF-8'), 0, 120);
            $options_li["amount_{$k}"] = zen_round(zen_add_tax($order->products[$i]['onetime_charges'], $order->products[$i]['tax']), $decimals);
            $options_li["quantity_{$k}"] = 1;
            //        $optionsLI["tax_$k"] = zen_round(zen_calculate_tax($order->products[$i]['onetime_charges'], $order->products[$i]['tax']), $decimals);
            $sub_total_li += $options_li["amount_{$k}"];
            //        $subTotalTax += $optionsLI["tax_$k"];
        }
        $number_of_line_items_processed = $k;
    }
    // end for loopthru all products
    // add line items for any surcharges added by order-total modules
    if ($surcharges > 0) {
        $number_of_line_items_processed++;
        $k = $number_of_line_items_processed;
        $options_li["item_name_{$k}"] = MODULES_PAYMENT_PAYPALSTD_LINEITEM_TEXT_SURCHARGES_SHORT;
        $options_li["amount_{$k}"] = $surcharges;
        $options_li["quantity_{$k}"] = 1;
        $sub_total_li += $surcharges;
    }
    // add line items for discounts such as gift certificates and coupons
    if ($credits_applied > 0) {
        $number_of_line_items_processed++;
        $k = $number_of_line_items_processed;
        $options_li["item_name_{$k}"] = MODULES_PAYMENT_PAYPALSTD_LINEITEM_TEXT_DISCOUNTS_SHORT;
        $options_li["amount_{$k}"] = -1 * $credits_applied;
        $options_li["quantity_{$k}"] = 1;
        $sub_total_li -= $credits_applied;
    }
    // Reformat properly
    // Replace & and = and % with * if found.
    // reformat properly according to API specs
    // Remove HTML markup from name if found
    for ($k = 1, $n = $number_of_line_items_processed + 1; $k < $n; $k++) {
        $options_li["item_name_{$k}"] = str_replace(['&', '=', '%'], '*', $options_li["item_name_{$k}"]);
        $options_li["item_name_{$k}"] = zen_clean_html($options_li["item_name_{$k}"], 'strong');
        $options_li["item_name_{$k}"] = substr($options_li["item_name_{$k}"], 0, 127);
        $options_li["amount_{$k}"] = round($options_li["amount_{$k}"], 2);
        if (isset($options_li["item_number_{$k}"])) {
            if ($options_li["item_number_{$k}"] == '') {
                unset($options_li["item_number_{$k}"]);
            } else {
                $options_li["item_number_{$k}"] = str_replace(['&', '=', '%'], '*', $options_li["item_number_{$k}"]);
                $options_li["item_number_{$k}"] = substr($options_li["item_number_{$k}"], 0, 127);
            }
        }
        //      if (isset($optionsLI["tax_$k"]) && ($optionsLI["tax_$k"] != '' || $optionsLI["tax_$k"] > 0)) {
        //        $optionsLI["tax_$k"] = round($optionsLI["tax_$k"], 2);
        //      }
    }
    // Sanity Check of line-item subtotals
    $options_li['num_cart_items'] = 0;
    for ($j = 1; $j < $k; $j++) {
        $item_amt = $options_li["amount_{$j}"];
        $item_qty = $options_li["quantity_{$j}"];
        $item_tax = $options_li["tax_{$j}"] ?? 0;
        $sum_of_line_items += $item_qty * $item_amt;
        $sum_of_line_tax += $item_qty * $item_tax;
        $options_li['num_cart_items']++;
    }
    $sum_of_line_items = round($sum_of_line_items, 2);
    $sum_of_line_tax = round($sum_of_line_tax, 2);
    if ($sum_of_line_items == 0) {
        $sum_of_line_tax = 0;
        $options_li = [];
        $discount_problems_flag = true;
        if ($options_st['shipping'] == $options_st['amount']) {
            $options_st['shipping'] = 0;
        }
    }
    //    // Sanity check -- if tax-included pricing is causing problems, remove the numbers and put them in a comment instead:
    //    $stDiffTaxOnly = (strval($sumOfLineItems - $sumOfLineTax - round($optionsST['amount'], 2)) + 0);
    //    if (DISPLAY_PRICE_WITH_TAX == 'true' && $stDiffTaxOnly == 0 && ($optionsST['tax_cart'] != 0 && $sumOfLineTax != 0)) {
    //      $optionsNB['DESC'] = 'Tax included in prices: ' . $sumOfLineTax . ' (' . $optionsST['tax_cart'] . ') ';
    //      $optionsST['tax_cart'] = 0;
    //      for ($k=1, $n=$numberOfLineItemsProcessed+1; $k<$n; $k++) {
    //        if (isset($optionsLI["tax_$k"])) unset($optionsLI["tax_$k"]);
    //      }
    //    }
    //    // Do sanity check -- if any of the line-item subtotal math doesn't add up properly, skip line-item details,
    //    // so that the order can go through even though PayPal isn't being flexible to handle Zen Cart's diversity
    //    if ((strval($subTotalTax) - strval($sumOfLineTax)) > 0.02) {
    //      $ipn_logging('getLineItemDetails 3', 'Tax Subtotal does not match sum of taxes for line-items. Tax details are being removed from line-item submission data.' . "\n" . $sumOfLineTax . ' ' . $subTotalTax . print_r(array_merge($optionsST, $optionsLI), true));
    //      for ($k=1, $n=$numberOfLineItemsProcessed+1; $k<$n; $k++) {
    //        if (isset($optionsLI["tax_$k"])) unset($optionsLI["tax_$k"]);
    //      }
    //      $subTotalTax = 0;
    //      $sumOfLineTax = 0;
    //    }
    //    // If coupons exist and there's a calculation problem, then it's likely that taxes are incorrect, so reset L_TAXAMTn values
    //    if ($creditsApplied > 0 && (strval($optionsST['tax_cart']) != strval($sumOfLineTax))) {
    //      $pre = $optionsLI;
    //      for ($k=1, $n=$numberOfLineItemsProcessed+1; $k<$n; $k++) {
    //        if (isset($optionsLI["tax_$k"])) unset($optionsLI["tax_$k"]);
    //      }
    //      $ipn_logging('getLineItemDetails 4', 'Coupons/Discounts have affected tax calculations, so tax details are being removed from line-item submission data.' . "\n" . $sumOfLineTax . ' ' . $optionsST['tax_cart'] . "\n" . print_r(array_merge($optionsST, $pre, $optionsNB), true) . "\nAFTER:" . print_r(array_merge($optionsST, $optionsLI, $optionsNB), TRUE));
    //      $subTotalTax = 0;
    //      $sumOfLineTax = 0;
    //    }
    // disable line-item tax details, leaving only TAXAMT subtotal as tax indicator
    for ($k = 1, $n = $number_of_line_items_processed + 1; $k < $n; $k++) {
        if (isset($options_li["tax_{$k}"])) {
            unset($options_li["tax_{$k}"]);
        }
    }
    // check subtotals
    if (strval($options_st['subtotal']) > 0 && strval($sub_total_li) > 0 && strval($sub_total_li) != strval($options_st['subtotal']) || strval($sub_total_li) - strval($sum_of_line_items) != 0) {
        ipn_logging('getLineItemDetails 5', 'Line-item subtotals do not add up properly. Line-item-details skipped.' . "\n" . strval($sum_of_line_items) . ' ' . strval($sub_total_li) . ' ' . print_r(array_merge($options_st, $options_li), true));
        $options_li = [];
        $options_li['item_name_0'] = MODULE_PAYMENT_PAYPAL_PURCHASE_DESCRIPTION_TITLE;
        $options_li['amount_0'] = $sum_of_line_items = $sub_total_li = $options_st['subtotal'];
    }
    // check whether discounts are causing a problem
    if (strval($options_st['subtotal']) < 0) {
        $pre = array_merge($options_st, $options_li);
        $options_st['subtotal'] = $options_st['amount'];
        $options_li = [];
        $options_li['item_name_0'] = MODULE_PAYMENT_PAYPAL_PURCHASE_DESCRIPTION_TITLE;
        $options_li['amount_0'] = $sum_of_line_items = $sub_total_li = $options_st['subtotal'];
        if ($options_st['amount'] < $options_st['tax_cart']) {
            $options_st['tax_cart'] = 0;
        }
        if ($options_st['amount'] < $options_st['shipping']) {
            $options_st['shipping'] = 0;
        }
        $discount_problems_flag = true;
        ipn_logging('getLineItemDetails 6', 'Discounts have caused the subtotal to calculate incorrectly. Line-item-details cannot be submitted.' . "\nBefore:" . print_r($pre, true) . "\nAfter:" . print_r(array_merge($options_st, $options_li), true));
    }
    // if amount or subtotal values are 0 (ie: certain OT modules disabled), we have to get subtotals manually
    if ((!isset($options_st['amount']) || $options_st['amount'] == 0 || $flag_subtotals_unknown_yet == true || $options_st['subtotal'] == 0) && $discount_problems_flag != true) {
        $options_st['subtotal'] = $sum_of_line_items;
        $options_st['tax_cart'] = $sum_of_line_tax;
        if ($sub_total_shipping > 0) {
            $options_st['shipping'] = $sub_total_shipping;
        }
        $options_st['amount'] = $sum_of_line_items + $options_st['tax_cart'] + $options_st['shipping'];
    }
    ipn_logging('getLineItemDetails 7 - subtotal comparisons', 'BEFORE line-item calcs: ' . print_r($subtotal_pre, true) . ' - AFTER doing line-item calcs: ' . print_r(array_merge($options_st, $options_li, $options_nb), true));
    // if subtotals are not adding up correctly, then skip sending any line-item or subtotal details to PayPal
    $st_all = round(strval($options_st['subtotal'] + $options_st['tax_cart'] + $options_st['shipping']), 2);
    $st_diff = strval($options_st['amount'] - $st_all);
    $st_diff_rounded = strval($st_all - round($options_st['amount'], 2)) + 0;
    // unset any subtotal values that are zero
    if (isset($options_st['subtotal']) && $options_st['subtotal'] == 0) {
        unset($options_st['subtotal']);
    }
    if (isset($options_st['tax_cart']) && $options_st['tax_cart'] == 0) {
        unset($options_st['tax_cart']);
    }
    if (isset($options_st['shipping']) && $options_st['shipping'] == 0) {
        unset($options_st['shipping']);
    }
    // tidy up all values so that they comply with proper format (rounded to 2 decimals for PayPal US use )
    if (!defined('PAYPALWPP_SKIP_LINE_ITEM_DETAIL_FORMATTING') || PAYPALWPP_SKIP_LINE_ITEM_DETAIL_FORMATTING != 'true' || in_array($order->info['currency'], ['JPY', 'NOK', 'HUF', 'TWD'])) {
        if (is_array($options_st)) {
            foreach ($options_st as $key => $value) {
                $options_st[$key] = round($value, (int) $currencies->get_decimal_places($restricted_currency) == 0 ? 0 : 2);
            }
        }
        if (is_array($options_li)) {
            foreach ($options_li as $key => $value) {
                if (substr($key, 0, 8) == 'tax_' && ($options_li[$key] == '' || $options_li[$key] == 0)) {
                    unset($options_li[$key]);
                } else if (strstr($key, 'amount')) {
                    $options_li[$key] = round($value, (int) $currencies->get_decimal_places($restricted_currency) == 0 ? 0 : 2);
                }
            }
        }
    }
    ipn_logging('getLineItemDetails 8', 'checking subtotals... ' . "\n" . print_r(array_merge(['calculated total' => round($st_all, (int) $currencies->get_decimal_places($restricted_currency) == 0 ? 0 : 2)], $options_st), true) . "\n-------------------\ndifference: " . ($st_diff + 0) . '  (abs+rounded: ' . ($st_diff_rounded + 0) . ')');
    if ($st_diff_rounded != 0) {
        ipn_logging('getLineItemDetails 9', 'Subtotals Bad. Skipping line-item/subtotal details');
        return [];
    }
    ipn_logging('getLineItemDetails 10', 'subtotals balance - okay' . "\nSubmitting:   " . print_r(array_merge($options_st, $options_li, $options_nb), true));
    // Send Subtotal and LineItem results back to be submitted to PayPal
    return array_merge($options_st, $options_li, $options_nb);
}
/**
 * Debug logging
 * @since ZC v1.3.8
 */
function ipn_logging(string $stage, ?string $message = ''): void
{
    if (defined('IPN_EXTRA_DEBUG_DETAILS') && IPN_EXTRA_DEBUG_DETAILS != '') {
        ipn_add_error_log($stage . ($message != '' ? ': ' . $message : ''));
    }
}
/**
 * @since ZC v1.3.0
 */
function ipn_add_error_log(string $message, $paypal_instance_id = ''): string
{
    if ($paypal_instance_id == '') {
        $paypal_instance_id = date('mdYGi');
    }
    $logfilename = 'includes/modules/payment/paypal/logs/ipn_' . $paypal_instance_id . '.log';
    if (defined('DIR_FS_LOGS')) {
        $logfilename = DIR_FS_LOGS . '/ipn_' . $paypal_instance_id . '.log';
    }
    $fp = @fopen($logfilename, 'a');
    if ($fp) {
        fwrite($fp, date('M d Y G:i') . ' -- ' . $message . "\n\n");
        fclose($fp);
    }
    return $logfilename;
}