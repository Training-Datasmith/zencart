<?php

declare (strict_types=1);
/**
 * paypal_curl.php communications class for PayPal Express Checkout / Website Payments Pro / Payflow Pro payment methods
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 30 Modified in v2.2.0 $
 */
/**
 * PayPal NVP (v124.0) and Payflow Pro (v4 HTTP API) implementation via cURL.
 * @since ZC v1.3.7
 */
if (!defined('PAYPAL_DEV_MODE')) {
    define('PAYPAL_DEV_MODE', 'false');
}
class paypal_curl extends base
{
    /**
     * What level should we log at? Valid levels are:
     *   1 - Log only severe errors.
     *   2 - Date/time of operation, operation name, elapsed time, success or failure indication.
     *   3 - Full text of requests and responses and other debugging messages.
     *
     * @access protected
     *
     * @var integer $_logLevel
     */
    public $_log_level = 3;
    /**
     * If we're logging, what directory should we create log files in?
     * Note that a log name coincides with a symlink, logging will
     * *not* be done to avoid security problems. File names are
     * <DateStamp>.PayflowPro.log.
     *
     * @access protected
     *
     * @var string $_logFile
     */
    public $_log_dir = DIR_FS_LOGS;
    /**
     * log output destination
     * @var string
     */
    protected $output_destination;
    /**
     * Debug or production?
     */
    protected $_server = 'sandbox';
    /**
     * URL endpoints -- defaults here are for three-token NVP implementation
     */
    public $_endpoints = ['live' => 'https://api-3t.paypal.com/nvp', 'sandbox' => 'https://api-3t.sandbox.paypal.com/nvp'];
    /**
     * Options for cURL. Defaults to preferred (constant) options.
     */
    protected $_curl_options = [
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => false,
        //CURLOPT_SSL_VERIFYPEER => FALSE, // Leave this line commented out! This should never be set to FALSE on a live site!
        //CURLOPT_CAINFO => '/local/path/to/cacert.pem', // for offline testing, this file can be obtained from http://curl.haxx.se/docs/caextract.html ... should never be used in production!
        CURLOPT_FORBID_REUSE => true,
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_POST => true,
    ];
    /**
     * Parameters that are always required and that don't change
     * request to request.
     */
    protected $_partner;
    protected $_vendor;
    protected $_user;
    protected $_pwd;
    protected $_version;
    protected $_signature;
    /**
     * nvp or payflow?
     */
    public $_mode = 'nvp';
    /**
     * Sales or authorizations? For the U.K. this will always be 'S'
     * (Sale) because of Switch and Solo cards which don't support
     * authorizations. The other option is 'A' for Authorization.
     * NOTE: 'A' is not supported for pre-signup-EC-boarding.
     */
    public $_trxtype = 'S';
    /**
     * Store the last-generated name/value list for debugging.
     */
    public $last_param_list;
    /**
     * Store the last-generated headers for debugging.
     */
    protected $last_headers;
    /**
     * submission values
     */
    protected $values = [];
    /**
     * Constructor. Sets up communication infrastructure.
     */
    public function __construct($params = [])
    {
        foreach ($params as $name => $value) {
            $this->set_param($name, $value);
        }
        $this->notify('NOTIFY_PAYPAL_CURL_CONSTRUCT', $params);
        if (!@is_writable($this->_log_dir)) {
            $this->_log_dir = DIR_FS_CATALOG . $this->_log_dir;
        }
        if (!@is_writable($this->_log_dir)) {
            $this->_log_dir = DIR_FS_LOGS;
        }
        if (!@is_writable($this->_log_dir)) {
            $this->_log_dir = DIR_FS_SQL_CACHE;
        }
    }
    /**
     * SetExpressCheckout
     *
     * Prepares to send customer to PayPal site so they can
     * log in and choose their funding source and shipping address.
     *
     * The token returned to this function is passed to PayPal in
     * order to link their PayPal selections to their cart actions.
     * @since ZC v1.3.7
     */
    public function set_express_checkout($return_url, $cancel_url, $options = [])
    {
        $values = $options;
        if ($this->_mode == 'payflow') {
            $values = array_merge($values, [
                'ACTION' => 'S',
                /* ACTION=S denotes SetExpressCheckout */
                'TENDER' => 'P',
                'TRXTYPE' => $this->_trxtype,
                'RETURNURL' => $return_url,
                'CANCELURL' => $cancel_url,
            ]);
        } elseif ($this->_mode == 'nvp') {
            if (!isset($values['PAYMENTREQUEST_0_PAYMENTACTION']) || $this->check_has_api_credentials() === false) {
                $values['PAYMENTREQUEST_0_PAYMENTACTION'] = $this->_trxtype == 'S' || $this->check_has_api_credentials() === false ? 'Sale' : 'Authorization';
            }
            $values['RETURNURL'] = urlencode((string) $return_url);
            $values['CANCELURL'] = urlencode((string) $cancel_url);
        }
        // convert country code key to proper key name for paypal 2.0 (needed when sending express checkout via payflow gateway, due to PayPal field naming inconsistency)
        if ($this->_mode == 'payflow') {
            if (!isset($values['SHIPTOCOUNTRY']) && isset($values['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE'])) {
                $values['SHIPTOCOUNTRY'] = $values['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE'];
                unset($values['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE']);
            }
            //if (isset($values['AMT'])) unset($values['AMT']);
        }
        // allow page-styling support -- see language file for definitions
        if (defined('MODULE_PAYMENT_PAYPALWPP_PAGE_STYLE')) {
            $values['PAGESTYLE'] = MODULE_PAYMENT_PAYPALWPP_PAGE_STYLE;
        }
        if (defined('MODULE_PAYMENT_PAYPAL_LOGO_IMAGE')) {
            $values['LOGOIMG'] = urlencode(MODULE_PAYMENT_LOGO_IMAGE);
        }
        if (defined('MODULE_PAYMENT_PAYPAL_CART_BORDER_COLOR')) {
            $values['CARTBORDERCOLOR'] = MODULE_PAYMENT_PAYPAL_CART_BORDER_COLOR;
        }
        if (defined('MODULE_PAYMENT_PAYPALWPP_HEADER_IMAGE')) {
            $values['HDRIMG'] = urlencode((string) MODULE_PAYMENT_PAYPALWPP_HEADER_IMAGE);
        }
        if (defined('MODULE_PAYMENT_PAYPALWPP_PAGECOLOR')) {
            $values['PAYFLOWCOLOR'] = MODULE_PAYMENT_PAYPALWPP_PAGECOLOR;
        }
        if (PAYPAL_DEV_MODE == 'true') {
            $this->log('SetExpressCheckout - breakpoint 1 - [' . print_r($values, true) . ']');
        }
        $this->values = $values;
        $this->notify('NOTIFY_PAYPAL_SETEXPRESSCHECKOUT');
        return $this->_request($this->values, 'SetExpressCheckout');
    }
    /**
     * GetExpressCheckoutDetails
     *
     * When customer returns from PayPal site, this retrieves their payment/shipping data for use in Zen Cart
     * @since ZC v1.3.7
     */
    public function get_express_checkout_details($token, $optional = [])
    {
        $values = array_merge($optional, ['TOKEN' => $token]);
        if ($this->_mode == 'payflow') {
            $values = array_merge($values, [
                'ACTION' => 'G',
                /* ACTION=G denotes GetExpressCheckoutDetails */
                'TENDER' => 'P',
                'TRXTYPE' => $this->_trxtype,
            ]);
        }
        $this->notify('NOTIFY_PAYPAL_GETEXPRESSCHECKOUTDETAILS');
        return $this->_request($values, 'GetExpressCheckoutDetails');
    }
    /**
     * DoExpressCheckoutPayment
     *
     * Completes the sale using PayPal as payment choice
     * @since ZC v1.3.7
     */
    public function do_express_checkout_payment(string $token, string $payer_id, $options = [])
    {
        $values = array_merge($options, ['TOKEN' => $token, 'PAYERID' => $payer_id]);
        if (PAYPAL_DEV_MODE == 'true') {
            $this->log('DoExpressCheckout - breakpoint 1 - [' . $token . ' ' . $payer_id . ' ' . "]\n\n[" . print_r($values, true) . ']', $token);
        }
        if ($this->_mode == 'payflow') {
            $values['ACTION'] = 'D';
            /* ACTION=D denotes DoExpressCheckoutPayment via Payflow */
            $values['TENDER'] = 'P';
            $values['TRXTYPE'] = $this->_trxtype;
            $values['NOTIFYURL'] = zen_href_link('ipn_main_handler.php', '', 'SSL', false, false, true);
        } elseif ($this->_mode == 'nvp') {
            if (!isset($values['PAYMENTREQUEST_0_PAYMENTACTION']) || $this->check_has_api_credentials() === false) {
                $values['PAYMENTREQUEST_0_PAYMENTACTION'] = $this->_trxtype == 'S' || $this->check_has_api_credentials() === false ? 'Sale' : 'Authorization';
            }
            $values['NOTIFYURL'] = urlencode((string) zen_href_link('ipn_main_handler.php', '', 'SSL', false, false, true));
        }
        $this->values = $values;
        $this->notify('NOTIFY_PAYPAL_DOEXPRESSCHECKOUTPAYMENT');
        if (PAYPAL_DEV_MODE == 'true') {
            $this->log('DoExpressCheckout - breakpoint 2 ' . print_r($this->values, true), $token);
        }
        return $this->_request($this->values, 'DoExpressCheckoutPayment');
    }
    /**
     * DoDirectPayment
     * Sends CC information to gateway for processing.
     *
     * Requires Website Payments Pro or Payflow Pro as merchant gateway.
     *
     * PAYMENTREQUEST_0_PAYMENTACTION = Authorization (auth/capt) or Sale (final)
     * @since ZC v1.3.7
     */
    public function do_direct_payment($cc, $cvv2 = '', $exp = '', $fname = null, $lname = null, $cc_type = '', $options = [], $nvp = [])
    {
        $values = $options;
        $values['ACCT'] = $cc;
        if ($cvv2 != '') {
            $values['CVV2'] = $cvv2;
        }
        $values['FIRSTNAME'] = $fname;
        $values['LASTNAME'] = $lname;
        if (isset($values['NAME'])) {
            unset($values['NAME']);
        }
        if ($this->_mode == 'payflow') {
            $values['EXPDATE'] = $exp;
            $values['TENDER'] = 'C';
            $values['TRXTYPE'] = $this->_trxtype;
            $values['VERBOSITY'] = 'MEDIUM';
            $values['NOTIFYURL'] = zen_href_link('ipn_main_handler.php', '', 'SSL', false, false, true);
        } elseif ($this->_mode == 'nvp') {
            $values = array_merge($values, $nvp);
            if (isset($values['ECI'])) {
                $values['ECI3DS'] = $values['ECI'];
                unset($values['ECI']);
            }
            $values['CREDITCARDTYPE'] = $cc_type == 'American Express' ? 'Amex' : $cc_type;
            $values['NOTIFYURL'] = urlencode((string) zen_href_link('ipn_main_handler.php', '', 'SSL', false, false, true));
            if (!isset($values['PAYMENTREQUEST_0_PAYMENTACTION'])) {
                $values['PAYMENTREQUEST_0_PAYMENTACTION'] = $this->_trxtype == 'S' ? 'Sale' : 'Authorization';
            }
            if (isset($values['COUNTRY'])) {
                unset($values['COUNTRY']);
            }
            if (isset($values['COMMENT1'])) {
                unset($values['COMMENT1']);
            }
            if (isset($values['COMMENT2'])) {
                unset($values['COMMENT2']);
            }
            if (isset($values['CUSTREF'])) {
                unset($values['CUSTREF']);
            }
        }
        $this->values = $values;
        $this->notify('NOTIFY_PAYPAL_DODIRECTPAYMENT');
        ksort($this->values);
        return $this->_request($this->values, 'DoDirectPayment');
    }
    /**
     * RefundTransaction
     *
     * Used to refund all or part of a given transaction
     * @since ZC v1.3.7
     */
    public function refund_transaction($o_id, $txn_id, $amount = 'Full', $note = '', $cur_code = 'USD')
    {
        if ($this->_mode == 'payflow') {
            $values['ORIGID'] = $txn_id;
            $values['TENDER'] = 'C';
            $values['TRXTYPE'] = 'C';
            $values['AMT'] = round((float) $amount, 2);
            if ($note != '') {
                $values['COMMENT2'] = substr((string) $note, 0, 128);
            }
        } elseif ($this->_mode == 'nvp') {
            $values['TRANSACTIONID'] = $txn_id;
            if ($amount != 'Full' && (float) $amount > 0) {
                $values['REFUNDTYPE'] = 'Partial';
                $values['CURRENCYCODE'] = $cur_code;
                $values['AMT'] = round((float) $amount, 2);
            } else {
                $values['REFUNDTYPE'] = 'Full';
            }
            if ($note != '') {
                $values['NOTE'] = substr((string) $note, 0, 255);
            }
        }
        return $this->_request($values, 'RefundTransaction');
    }
    /**
     * DoVoid
     *
     * Used to void a previously authorized transaction
     * @since ZC v1.3.7
     */
    public function do_void($txn_id, $note = '')
    {
        if ($this->_mode == 'payflow') {
            $values['ORIGID'] = $txn_id;
            $values['TENDER'] = 'C';
            $values['TRXTYPE'] = 'V';
            if ($note != '') {
                $values['COMMENT2'] = substr((string) $note, 0, 128);
            }
        } elseif ($this->_mode == 'nvp') {
            $values['AUTHORIZATIONID'] = $txn_id;
            if ($note != '') {
                $values['NOTE'] = substr((string) $note, 0, 255);
            }
        }
        return $this->_request($values, 'DoVoid');
    }
    /**
     * DoAuthorization
     *
     * Used to authorize part of a previously placed order which was initiated as authType of Order
     * @since ZC v1.3.7
     */
    public function do_authorization($txn_id, $amount = 0, $currency = 'USD', $entity = 'Order')
    {
        $values['TRANSACTIONID'] = $txn_id;
        $values['AMT'] = round((float) $amount, 2);
        $values['TRANSACTIONENTITY'] = $entity;
        $values['CURRENCYCODE'] = $currency;
        return $this->_request($values, 'DoAuthorization');
    }
    /**
     * DoReauthorization
     *
     * Used to reauthorize a previously-authorized order which has expired
     * @since ZC v1.3.7
     */
    public function do_reauthorization($txn_id, $amount = 0, $currency = 'USD')
    {
        $values['AUTHORIZATIONID'] = $txn_id;
        $values['AMT'] = round((float) $amount, 2);
        $values['CURRENCYCODE'] = $currency;
        return $this->_request($values, 'DoReauthorization');
    }
    /**
     * DoCapture
     *
     * Used to capture part or all of a previously placed order which was only authorized
     * @since ZC v1.3.7
     */
    public function do_capture($txn_id, $amount = 0, $currency = 'USD', $capture_type = 'Complete', $inv_num = '', $note = '')
    {
        if ($this->_mode == 'payflow') {
            $values['ORIGID'] = $txn_id;
            $values['TENDER'] = 'C';
            $values['TRXTYPE'] = 'D';
            $values['VERBOSITY'] = 'MEDIUM';
            if ($inv_num != '') {
                $values['INVNUM'] = $inv_num;
            }
            if ($note != '') {
                $values['COMMENT2'] = substr((string) $note, 0, 128);
            }
        } elseif ($this->_mode == 'nvp') {
            $values['AUTHORIZATIONID'] = $txn_id;
            $values['COMPLETETYPE'] = $capture_type;
            $values['AMT'] = round((float) $amount, 2);
            $values['CURRENCYCODE'] = $currency;
            if ($inv_num != '') {
                $values['INVNUM'] = $inv_num;
            }
            if ($note != '') {
                $values['NOTE'] = substr((string) $note, 0, 255);
            }
        }
        return $this->_request($values, 'DoCapture');
    }
    /**
     * ManagePendingTransactionStatus
     *
     * Accept/Deny pending FMF transactions
     * @since ZC v1.3.9a
     */
    public function manage_pending_transaction_status($txn_id, $action)
    {
        if (!in_array($action, ['Accept', 'Deny'])) {
            return false;
        }
        $values['TRANSACTIONID'] = $txn_id;
        $values['ACTION'] = $action;
        return $this->_request($values, 'ManagePendingTransactionStatus');
    }
    /**
     * GetTransactionDetails
     *
     * Used to read data from PayPal for a given transaction
     * @since ZC v1.3.7
     */
    public function get_transaction_details($txn_id)
    {
        if ($this->_mode == 'payflow') {
            $values['ORIGID'] = $txn_id;
            $values['TENDER'] = 'C';
            $values['TRXTYPE'] = 'I';
            $values['VERBOSITY'] = 'MEDIUM';
        } elseif ($this->_mode == 'nvp') {
            $values['TRANSACTIONID'] = $txn_id;
        }
        return $this->_request($values, 'GetTransactionDetails');
    }
    /**
     * TransactionSearch
     *
     * Used to read data from PayPal for specified transaction criteria
     * @since ZC v1.3.7.1
     */
    public function transaction_search($startdate, $txn_id = '', $email = '', $options = null)
    {
        if ($this->_mode == 'payflow') {
            $values['CUSTREF'] = $txn_id;
            $values['TENDER'] = 'C';
            $values['TRXTYPE'] = 'I';
            $values['VERBOSITY'] = 'MEDIUM';
        } elseif ($this->_mode == 'nvp') {
            $values['STARTDATE'] = $startdate;
            $values['TRANSACTIONID'] = $txn_id;
            $values['EMAIL'] = $email;
            if (is_array($options)) {
                $values = array_merge($values, $options);
            }
        }
        return $this->_request($values, 'TransactionSearch');
    }
    /**
     * Set a parameter as passed.
     * @since ZC v1.3.7
     */
    public function set_param($name, $value): void
    {
        $name = '_' . $name;
        $this->{$name} = $value;
    }
    /**
     * Set CURL options.
     * @since ZC v1.3.7
     */
    public function set_curl_option($name, $value): void
    {
        $this->_curl_options[$name] = $value;
    }
    /**
     * Send a request to endpoint.
     * @since ZC v1.3.7
     */
    public function _request(array $values, string $operation, $request_id = null): array|false
    {
        if ($this->_mode == 'NOTCONFIGURED') {
            return ['RESULT' => 'PayPal credentials not set. Cannot proceed.'];
        }
        if ($this->check_has_api_credentials() === false && !in_array($operation, ['SetExpressCheckout', 'GetExpressCheckoutDetails', 'DoExpressCheckoutPayment'])) {
            return ['RESULT' => 'Unauthorized: Unilateral'];
        }
        if (PAYPAL_DEV_MODE == 'true') {
            $this->log('_request - breakpoint 1 - ' . $operation . "\n" . print_r($values, true));
        }
        $start = $this->_get_microseconds();
        if ($this->_mode == 'nvp') {
            $values['METHOD'] = $operation;
        }
        if ($this->_mode == 'payflow') {
            $values['REQUEST_ID'] = time();
        }
        // convert currency code to proper key name for nvp
        if ($this->_mode == 'nvp') {
            $variable_name = $operation == 'setExpressCheckout' || $operation == 'doExpressCheckoutPayment' ? 'PAYMENTREQUEST_0_CURRENCYCODE' : 'CURRENCYCODE';
            if (!isset($values[$variable_name]) && isset($values['CURRENCY'])) {
                $values[$variable_name] = $values['CURRENCY'];
                unset($values['CURRENCY']);
            }
        }
        // request-id must be unique within 30 days
        if ($request_id === null) {
            $request_id = \bin2hex(\random_bytes(16));
        }
        $headers[] = 'Content-Type: text/namevalue';
        $headers[] = 'X-VPS-Timeout: 90';
        $headers[] = 'X-VPS-VIT-Client-Type: PHP/cURL';
        if ($this->_mode == 'payflow') {
            $headers[] = 'X-VPS-VIT-Integration-Product: PHP::Zen Cart(R) - PayPal/Payflow Pro';
        } elseif ($this->_mode == 'nvp') {
            $headers[] = 'X-VPS-VIT-Integration-Product: PHP::Zen Cart(R) - PayPal/NVP';
        }
        $headers[] = 'X-VPS-VIT-Integration-Version: 2.1.0';
        $this->last_headers = $headers;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->_endpoints[$this->_server]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_build_name_value_list($values));
        foreach ($this->_curl_options as $name => $value) {
            curl_setopt($ch, $name, $value);
        }
        $response = curl_exec($ch);
        $comm_error = curl_error($ch);
        $comm_err_no = curl_errno($ch);
        if ($comm_err_no == 35) {
            trigger_error('ALERT: Could not process PayPal transaction via normal CURL communications. Your server is encountering connection problems using TLS 1.2 ... because your hosting company cannot autonegotiate a secure protocol with modern security protocols. We will try the transaction again, but this is resulting in a very long delay for your customers, and could result in them attempting duplicate purchases. Get your hosting company to update their TLS capabilities ASAP.', E_USER_NOTICE);
            curl_setopt($ch, CURLOPT_SSLVERSION, 6);
            // Using the defined value of 6 instead of CURL_SSLVERSION_TLSv1_2 since these outdated hosts also don't properly implement this constant either.
            $response = curl_exec($ch);
            $comm_error = curl_error($ch);
            $comm_err_no = curl_errno($ch);
        }
        $comm_info = @curl_getinfo($ch);
        print_r($comm_info, true);
        $errors = $comm_err_no != 0 ? "\n(" . $comm_err_no . ') ' . $comm_error : '';
        $response .= '&CURL_ERRORS=' . ($comm_err_no != 0 ? urlencode('(' . $comm_err_no . ') ' . $comm_error) : '');
        // do debug/logging
        if (!in_array($operation, ['GetTransactionDetails', 'TransactionSearch']) || in_array($operation, ['GetTransactionDetails', 'TransactionSearch']) && !strstr($response, '&ACK=Success')) {
            $this->_log_transaction($operation, $this->_get_elapsed($start), $response, $errors . ($comm_err_no != 0 ? "\n" . print_r($comm_info, true) : ''));
        }
        if ($response) {
            return $this->_parse_name_value_list($response);
        }
        return false;
    }
    /**
     * Take an array of name-value pairs and return a properly
     * formatted list. Enforces the following rules:
     *
     *   - Names must be uppercase, all characters must match [A-Z].
     *   - Values cannot contain quotes.
     *   - If values contain & or =, the name has the length appended to
     *     it in brackets (NAME[4] for a 4-character value.
     *
     * If any of the "cannot" conditions are violated the function
     * returns false, and the caller must abort and not proceed with
     * the transaction.
     * @since ZC v1.3.7
     */
    public function _build_name_value_list($pairs)
    {
        // Add the parameters that are always sent.
        $commpairs = [];
        // generic:
        if ($this->_user != '') {
            $commpairs['USER'] = str_replace('+', '%2B', trim((string) $this->_user));
        }
        if ($this->_pwd != '') {
            $commpairs['PWD'] = trim((string) $this->_pwd);
        }
        // PRO2.0 options:
        if ($this->_partner != '') {
            $commpairs['PARTNER'] = trim((string) $this->_partner);
        }
        if ($this->_vendor != '') {
            $commpairs['VENDOR'] = trim((string) $this->_vendor);
        }
        // NVP-specific options:
        if ($this->_version != '') {
            $commpairs['VERSION'] = trim((string) $this->_version);
        }
        if ($this->_signature != '') {
            $commpairs['SIGNATURE'] = trim((string) $this->_signature);
        }
        // Use sandbox credentials if defined and sandbox selected
        if ($this->_server == 'sandbox' && defined('MODULE_PAYMENT_PAYPALWPP_SANDBOX_APIUSERNAME') && MODULE_PAYMENT_PAYPALWPP_SANDBOX_APIUSERNAME != '' && defined('MODULE_PAYMENT_PAYPALWPP_SANDBOX_APIPASSWORD') && MODULE_PAYMENT_PAYPALWPP_SANDBOX_APIPASSWORD != '' && defined('MODULE_PAYMENT_PAYPALWPP_SANDBOX_APISIGNATURE') && MODULE_PAYMENT_PAYPALWPP_SANDBOX_APISIGNATURE != '') {
            $commpairs['USER'] = str_replace('+', '%2B', trim((string) MODULE_PAYMENT_PAYPALWPP_SANDBOX_APIPASSWORD));
            $commpairs['PWD'] = trim((string) MODULE_PAYMENT_PAYPALWPP_SANDBOX_APIPASSWORD);
            $commpairs['SIGNATURE'] = trim((string) MODULE_PAYMENT_PAYPALWPP_SANDBOX_APISIGNATURE);
        }
        // Adjustments if Micropayments account profile details have been set
        if (defined('MODULE_PAYMENT_PAYPALWPP_MICROPAY_THRESHOLD') && MODULE_PAYMENT_PAYPALWPP_MICROPAY_THRESHOLD != '' && ($pairs['AMT'] > 0 && $pairs['AMT'] < strval(MODULE_PAYMENT_PAYPALWPP_MICROPAY_THRESHOLD) || $pairs['PAYMENTREQUEST_0_AMT'] > 0 && $pairs['PAYMENTREQUEST_0_AMT'] < strval(MODULE_PAYMENT_PAYPALWPP_MICROPAY_THRESHOLD) || $pairs['METHOD'] == 'GetExpressCheckoutDetails' && isset($_SESSION['using_micropayments']) && $_SESSION['using_micropayments'] == true) && defined('MODULE_PAYMENT_PAYPALWPP_MICROPAY_APIUSERNAME') && MODULE_PAYMENT_PAYPALWPP_MICROPAY_APIUSERNAME != '' && defined('MODULE_PAYMENT_PAYPALWPP_MICROPAY_APIPASSWORD') && MODULE_PAYMENT_PAYPALWPP_MICROPAY_APIPASSWORD != '' && defined('MODULE_PAYMENT_PAYPALWPP_MICROPAY_APISIGNATURE') && MODULE_PAYMENT_PAYPALWPP_MICROPAY_APISIGNATURE != '') {
            $commpairs['USER'] = str_replace('+', '%2B', trim((string) MODULE_PAYMENT_PAYPALWPP_MICROPAY_APIUSERNAME));
            $commpairs['PWD'] = trim((string) MODULE_PAYMENT_PAYPALWPP_MICROPAY_APIPASSWORD);
            $commpairs['SIGNATURE'] = trim((string) MODULE_PAYMENT_PAYPALWPP_MICROPAY_APISIGNATURE);
            $_SESSION['using_micropayments'] = $pairs['METHOD'] == 'DoExpressCheckoutPayment' ? false : true;
        }
        // Accelerated/Unilateral Boarding support:
        if ($this->check_has_api_credentials() == false) {
            $commpairs['SUBJECT'] = STORE_OWNER_EMAIL_ADDRESS;
            $commpairs['USER'] = '';
            $commpairs['PWD'] = '';
            $commpairs['SIGNATURE'] = '';
        }
        $pairs = array_merge($pairs, $commpairs);
        $string = [];
        foreach ($pairs as $name => $value) {
            if (preg_match('/[^A-Z_0-9]/', (string) $name)) {
                if (PAYPAL_DEV_MODE == 'true') {
                    $this->log('_buildNameValueList - datacheck - ABORTING - preg_match found invalid submission key: ' . $name . ' (' . $value . ')');
                }
                return false;
            }
            // remove quotation marks
            $value = str_replace('"', '', $value);
            // if the value contains a & or = symbol, handle it differently
            if ($this->_mode == 'payflow' && (str_contains($value, '&') || str_contains($value, '='))) {
                $name = str_replace(['PAYMENTREQUEST_0_', 'PAYMENTINFO_0_'], '', $name);
                // For Payflow, remove NVP v63.0+ extras from name
                $string[] = $name . '[' . strlen($value) . ']=' . $value;
                if (PAYPAL_DEV_MODE == 'true') {
                    $this->log('_buildNameValueList - datacheck - adding braces and string count to: ' . $value . ' (' . $name . ')');
                }
            } else {
                if ($this->_mode == 'nvp' && ((strstr((string) $name, 'SHIPTO') || strstr((string) $name, 'L_NAME') || strstr((string) $name, 'L_PAYMENTREQUEST_0_NAME') || strstr((string) $name, '_DESC')) && (str_contains($value, '&') || str_contains($value, '=')))) {
                    $value = urlencode($value);
                }
                $string[] = $name . '=' . $value;
            }
        }
        $this->last_param_list = implode('&', $string);
        $this->notify('NOTIFY_PAYPAL_CURL_BUILDNAMEVALUELIST', $string);
        return $this->last_param_list;
    }
    /**
     * Take a name/value response string and parse it into an
     * associative array. Doesn't handle length tags in the response
     * as they should not be present.
     * @since ZC v1.3.7
     * @return mixed[]
     */
    public function _parse_name_value_list($string): array
    {
        $string = str_replace('&amp;', '|', $string ?? '');
        $pairs = explode('&', str_replace(["\r\n", "\n"], '', $string));
        //$this->log('['.$string . "]\n\n[" . print_r($pairs, true) .']');
        $values = [];
        foreach ($pairs as $pair) {
            $arr = explode('=', $pair, 2);
            $name = $arr[0] ?? '';
            $value = $arr[1] ?? '';
            $values[$name] = str_replace('|', '&amp;', $value);
        }
        return $values;
    }
    /**
     * Log the current transaction depending on the current log level.
     *
     * @access protected
     *
     * @param string $operation  The operation called.
     * @param integer $elapsed   Microseconds taken.
     * @param object $response   The response.
     * @since ZC v1.3.7
     */
    public function _log_transaction(string $operation, $elapsed, $response, string $errors): void
    {
        $values = $this->_parse_name_value_list($response);
        $token = $values['TOKEN'] ?? '';
        $token = preg_replace('/[^0-9.A-Z\-]/', '', urldecode((string) $token));
        $success = false;
        if ($response) {
            if (isset($values['RESULT']) && $values['RESULT'] == 0 || isset($values['ACK']) && (strstr($values['ACK'], 'Success') || strstr($values['ACK'], 'SuccessWithWarning')) && !strstr($values['ACK'], 'Failure')) {
                $success = true;
            }
        }
        $message = date('Y-m-d h:i:s') . "\n-------------------\n";
        $message .= '(' . $this->_server . ' transaction) --> ' . $this->_endpoints[$this->_server] . "\n";
        $message .= 'Request Headers: ' . "\n" . $this->_sanitize_log($this->last_headers) . "\n\n";
        $message .= 'Request Parameters: {' . $operation . '} ' . "\n" . urldecode($this->_sanitize_log($this->_parse_name_value_list($this->last_param_list))) . "\n\n";
        $message .= 'Response: ' . "\n" . urldecode($this->_sanitize_log($values)) . $errors;
        if ($this->_log_level > 0 || $success == false) {
            $this->log($message, $token);
            // extra debug email: //
            if (MODULE_PAYMENT_PAYPALWPP_DEBUGGING == 'Log and Email') {
                zen_mail(STORE_NAME, STORE_OWNER_EMAIL_ADDRESS, 'PayPal Debug log - ' . $operation, $message, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, ['EMAIL_MESSAGE_HTML' => nl2br($message)], 'debug');
            }
            $this->log($operation . ', Elapsed: ' . $elapsed . 'ms -- ' . ($values['ACK'] ?? ($success ? 'Succeeded' : 'Failed')) . $errors, $token);
            if (!$response) {
                $this->log('No response from server' . $errors, $token);
            } else if (isset($values['RESULT']) && $values['RESULT'] != 0 || isset($values['ACK']) && strstr($values['ACK'], 'Failure')) {
                $this->log($response . $errors, $token);
            }
        }
    }
    /**
     * Strip sensitive information (passwords, credit card numbers, cvv2 codes) from requests/responses.
     *
     * @access protected
     *
     * @param mixed $log  The log to sanitize.
     * @return string  The sanitized (and string-ified, if necessary) log.
     * @since ZC v1.3.7
     */
    public function _sanitize_log($log, $allsensitive = false)
    {
        if (is_array($log)) {
            foreach (array_keys($log) as $key) {
                switch (strtolower((string) $key)) {
                    case 'pwd':
                    case 'cvv2':
                        $log[$key] = str_repeat('*', strlen((string) $log[$key]));
                        break;
                    case 'signature':
                    case 'acct':
                        $log[$key] = str_repeat('*', strlen(substr((string) $log[$key], 0, -4))) . substr((string) $log[$key], -4);
                        break;
                    case 'solutiontype':
                        unset($log[$key]);
                        break;
                }
                if ($allsensitive && in_array($key, ['BUTTONSOURCE', 'VERSION', 'SIGNATURE', 'USER', 'VENDOR', 'PARTNER', 'PWD', 'VERBOSITY'])) {
                    unset($log[$key]);
                }
            }
            return print_r($log, true);
        }
        return $log;
    }
    /**
     * @since ZC v1.3.7
     */
    public function log(string $message, $token = ''): void
    {
        static $token_hash;
        if ($token_hash == '') {
            $token_hash = '_' . zen_create_random_value(4);
        }
        $this->output_destination = 'File';
        $this->notify('PAYPAL_CURL_LOG', $token, $token_hash);
        if ($token == '' && !empty($_SESSION['paypal_ec_token'])) {
            $token = $_SESSION['paypal_ec_token'];
        }
        if ($token == '') {
            $token = time();
        }
        $token .= $token_hash;
        if ($this->output_destination == 'File') {
            $file = $this->_log_dir . '/' . 'Paypal_CURL_' . $token . '.log';
            if ($fp = @fopen($file, 'a')) {
                fwrite($fp, $message . "\n\n");
                fclose($fp);
            }
        }
    }
    /**
     * Check whether API credentials are supplied, or if is blank
     *
     * @since ZC v1.3.9a
     */
    public function check_has_api_credentials(): bool
    {
        return $this->_mode == 'nvp' && ($this->_user == '' || $this->_pwd == '') ? false : true;
    }
    /**
     * Return the current time including microseconds.
     *
     * @access protected
     *
     * @return integer  Current time with microseconds.
     * @since ZC v1.3.7
     */
    public function _get_microseconds(): float
    {
        [$ms, $s] = explode(' ', microtime());
        return floor($ms * 1000) + 1000 * $s;
    }
    /**
     * Return the difference between now and $start in microseconds.
     *
     * @access protected
     *
     * @param integer $start  Start time including microseconds.
     *
     * @return integer  Number of microseconds elapsed since $start
     * @since ZC v1.3.7
     */
    public function _get_elapsed($start): int|float
    {
        return $this->_get_microseconds() - $start;
    }
}
/**
 * Convert HTML comments to readable text
 * @param string $string
 * @since ZC v1.3.9a
 */
function zen_uncomment($string): string
{
    return str_replace(['<!-- ', ' -->'], ['[', ']'], $string);
}