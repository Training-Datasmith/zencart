<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Oct 25 Modified in v2.2.0 $
 * @since ZC v1.5.8
 */
class Customer extends base
{
    //- customers::customers_authorization values
    public const AUTH_OK = 0;
    //- customer is authorized
    public const AUTH_NO_BROWSE = 1;
    //- customer must be authorized to browse
    public const AUTH_NO_PRICES = 2;
    //- customer can browse, but no prices until authorized
    public const AUTH_NO_PURCHASE = 3;
    //- customer can browse with prices, but no cart/checkout until authorized
    public const AUTH_BANNED = 4;
    //- customer is banned
    protected ?int $customer_id = null;
    protected bool $is_logged_in = false;
    protected bool $is_in_guest_checkout = false;
    protected array $data = [];
    public function __construct($customer_id = null)
    {
        $this->is_logged_in = $this->someone_is_logged_in();
        $this->is_in_guest_checkout = $this->is_in_guest_checkout();
        if (empty($customer_id) && $this->is_logged_in) {
            $this->set_customer_id_from_session();
        }
        if (!empty($customer_id) && empty($this->customer_id)) {
            $this->customer_id = $customer_id;
        }
        if (!empty($this->customer_id)) {
            $this->load($this->customer_id);
            // if we have no record for this customer, reset it to null
            if (empty($this->data)) {
                $this->customer_id = null;
                $this->is_logged_in = false;
            }
        }
    }
    /**
     * @since ZC v2.0.0
     */
    protected static function get_customer_wholesale_info(): array
    {
        static $wholesale_info;
        if (!isset($wholesale_info)) {
            $wholesale_info = ['is_wholesale' => false, 'wholesale_tier' => 0, 'is_tax_exempt' => false];
            if (WHOLESALE_PRICING_CONFIG !== 'false' && zen_is_logged_in() && !zen_in_guest_checkout()) {
                global $db;
                $wholesale = $db->Execute('SELECT customers_whole
                       FROM ' . TABLE_CUSTOMERS . '
                      WHERE customers_id = ' . (int) $_SESSION['customer_id'] . '
                      LIMIT 1');
                if (!$wholesale->EOF && $wholesale->fields['customers_whole'] !== '0') {
                    $wholesale_info = ['is_wholesale' => true, 'wholesale_tier' => (int) $wholesale->fields['customers_whole'], 'is_tax_exempt' => WHOLESALE_PRICING_CONFIG === 'Tax Exempt'];
                }
            }
            global $zco_notifier;
            $zco_notifier->notify('NOTIFY_GET_CUSTOMER_WHOLESALE_INFO', $wholesale->fields ?? [], $wholesale_info);
        }
        return $wholesale_info;
    }
    /**
     * @since ZC v2.0.0
     */
    public static function is_wholesale_customer(): bool
    {
        $wholesale_info = Customer::get_customer_wholesale_info();
        return $wholesale_info['is_wholesale'];
    }
    /**
     * @since ZC v2.0.0
     */
    public static function is_tax_exempt(): bool
    {
        $wholesale_info = Customer::get_customer_wholesale_info();
        $is_tax_exempt = $wholesale_info['is_tax_exempt'];
        global $zco_notifier;
        $zco_notifier->notify('NOTIFY_CUSTOMER_IS_TAX_EXEMPT', [], $is_tax_exempt);
        return (bool) $is_tax_exempt;
    }
    /**
     * @since ZC v2.0.0
     */
    public static function get_customer_wholesale_tier(): int
    {
        $wholesale_info = Customer::get_customer_wholesale_info();
        return $wholesale_info['wholesale_tier'];
    }
    /**
     * @since ZC v2.2.0
     */
    public static function create_password_reset_token(string $email_address): array|false
    {
        global $db;
        $sql = 'SELECT customers_firstname, customers_lastname, customers_id, customers_email_address
               FROM ' . TABLE_CUSTOMERS . '
              WHERE customers_email_address = :emailAddress
                AND customers_authorization != ' . self::AUTH_BANNED;
        $sql = $db->bind_vars($sql, ':emailAddress', $email_address, 'string');
        $check_customer = $db->Execute($sql, 1);
        if ($check_customer->EOF) {
            return false;
        }
        $length = defined('PASSWORD_RESET_TOKEN_LENGTH') ? constant('PASSWORD_RESET_TOKEN_LENGTH') : 24;
        if ($length < 12 || $length > 100) {
            // under 12 is impractical; over 100 is too large for db field
            $length = 24;
        }
        $token = zen_create_random_value($length);
        $sql = 'DELETE FROM ' . TABLE_CUSTOMER_PASSWORD_RESET_TOKENS . ' WHERE customer_id = :customerID';
        $sql = $db->bind_vars($sql, ':customerID', $check_customer->fields['customers_id'], 'integer');
        $db->Execute($sql);
        $sql = 'INSERT INTO ' . TABLE_CUSTOMER_PASSWORD_RESET_TOKENS . ' (customer_id, token) VALUES (:customerID, :token)';
        $sql = $db->bind_vars($sql, ':token', $token, 'string');
        $sql = $db->bind_vars($sql, ':customerID', $check_customer->fields['customers_id'], 'integer');
        $db->Execute($sql);
        $check_customer->fields['token'] = $token;
        return $check_customer->fields;
    }
    /**
     * @since ZC v2.2.0
     */
    public static function get_password_reset_token_info(string $reset_token): array|false
    {
        global $db;
        $token_valid_minutes = self::get_password_reset_token_minutes_valid();
        $sql = 'SELECT c.customers_nick, c.customers_id
                FROM   ' . TABLE_CUSTOMERS . ' c, ' . TABLE_CUSTOMER_PASSWORD_RESET_TOKENS . " ct\n                WHERE  ct.token = :reset_token AND c.customers_id = ct.customer_id AND ct.created_at > DATE_SUB(CURRENT_TIMESTAMP, INTERVAL {$token_valid_minutes} MINUTE)";
        $sql = $db->bind_vars($sql, ':reset_token', $reset_token, 'string');
        $result = $db->Execute($sql);
        if ($result->EOF) {
            return false;
        }
        return $result->fields;
    }
    /**
     * @since ZC v2.2.0
     */
    public static function get_password_reset_token_for_email(string $email_address): array|false
    {
        global $db;
        $token_valid_minutes = self::get_password_reset_token_minutes_valid();
        $sql = 'SELECT ct.*
               FROM ' . TABLE_CUSTOMER_PASSWORD_RESET_TOKENS . ' ct
                    INNER JOIN ' . TABLE_CUSTOMERS . " c\n                        ON ct.customer_id = c.customers_id\n              WHERE c.customers_email_address = :email_address\n                AND ct.created_at > DATE_SUB(CURRENT_TIMESTAMP, INTERVAL {$token_valid_minutes} MINUTE)\n              ORDER BY ct.created_at DESC";
        $sql = $db->bind_vars($sql, ':email_address', $email_address, 'string');
        $result = $db->Execute($sql, 1);
        return $result->EOF ? false : $result->fields;
    }
    /**
     * @since ZC v2.2.0
     */
    public static function get_password_reset_token_minutes_valid(): int
    {
        $token_valid_minutes = defined('PASSWORD_RESET_TOKEN_MINUTES_VALID') ? (int) constant('PASSWORD_RESET_TOKEN_MINUTES_VALID') : 60;
        if ($token_valid_minutes < 1 || $token_valid_minutes > 1440) {
            return 60;
        }
        return $token_valid_minutes;
    }
    /**
     * @since ZC v1.5.8
     */
    public function get_data(?string $element = null)
    {
        if (empty($element)) {
            return $this->data;
        }
        if (empty($this->data) || !isset($this->data[$element])) {
            return null;
        }
        return $this->data[$element];
    }
    /**
     * @since ZC v1.5.8
     */
    public function get_current_customer_id(): int
    {
        if (empty($this->customer_id)) {
            $this->set_customer_id_from_session();
        }
        return (int) $this->customer_id;
    }
    /**
     * @since ZC v1.5.8
     */
    public function set_customer_id_from_session(): int
    {
        if (!empty($_SESSION['customer_id'])) {
            $this->customer_id = (int) $_SESSION['customer_id'];
        }
        return (int) $this->customer_id;
    }
    /**
     * Return whether the indicated customer is currently logged into the site.
     * If no customer is specified, we check the one already assigned to this class
     * @since ZC v1.5.8
     */
    public function is_same_as_logged_in(?int $id_to_check = null): bool
    {
        if (empty($id_to_check)) {
            $id_to_check = $this->customer_id;
        }
        $is_currently_logged_in = !empty($_SESSION['customer_id']) && $id_to_check === (int) $_SESSION['customer_id'];
        $this->notify('NOTIFY_ZEN_IS_CURRENTLY_LOGGED_IN', null, $is_currently_logged_in);
        return (bool) $is_currently_logged_in;
    }
    /**
     * Return whether "any" customer is currently logged into the site.
     * @since ZC v1.5.8
     */
    public function someone_is_logged_in(): bool
    {
        $is_logged_in = !empty($_SESSION['customer_id']);
        $this->notify('NOTIFY_ZEN_IS_LOGGED_IN', null, $is_logged_in);
        return (bool) $is_logged_in;
    }
    /**
     * @since ZC v1.5.8
     */
    public function do_login_lookup_by_email(string $email): array|false
    {
        global $db;
        $sql = 'SELECT customers_id, customers_password, customers_authorization
               FROM ' . TABLE_CUSTOMERS . '
              WHERE customers_email_address = :emailAddress';
        $sql = $db->bind_vars($sql, ':emailAddress', $email, 'string');
        $result = $db->Execute($sql, 1);
        if ($result->EOF) {
            return false;
        }
        return $result->fields;
    }
    /**
     * @since ZC v1.5.8
     */
    public function login(int $customer_id, $restore_cart = true): bool
    {
        global $db;
        if (empty($customer_id)) {
            return false;
        }
        // @TODO
        // what if already logged in?
        if (!$this->customer_exists_in_database($customer_id)) {
            return false;
        }
        // fire notifier to check whether login should be allowed?
        //@TODO        $this->notify('NOTIFY_?LOGIN_ATTEMPT', null, $is_logged_in);
        // -----
        // Load the customer's information from the database and set the appropriate
        // session variables.
        //
        $this->load($customer_id);
        if (empty($this->data)) {
            return false;
        }
        // @TODO - delete this if we collapse the Info table
        // enforce db integrity: make sure related record exists
        if (empty($this->data['date_account_created'])) {
            $sql = 'INSERT IGNORE INTO ' . TABLE_CUSTOMERS_INFO . ' (customers_info_id) VALUES (:customersID)';
            $sql = $db->bind_vars($sql, ':customersID', $customer_id, 'integer');
            $db->Execute($sql);
        }
        // update last login
        $sql = 'UPDATE ' . TABLE_CUSTOMERS_INFO . '
                SET customers_info_date_of_last_logon = now(),
                    customers_info_number_of_logons = IF(customers_info_number_of_logons, customers_info_number_of_logons+1, 1)
              WHERE customers_info_id = ' . (int) $customer_id;
        $db->Execute($sql);
        $sql = 'UPDATE ' . TABLE_CUSTOMERS . "\n                SET last_login_ip = '" . zen_db_input(zen_get_ip_address()) . "'\n              WHERE customers_id = " . (int) $customer_id;
        $db->Execute($sql);
        $this->clear_password_reset_tokens($customer_id);
        // these session variables are used in various places across the catalog
        $_SESSION['customer_id'] = (int) $customer_id;
        $_SESSION['customers_email_address'] = $this->data['customers_email_address'];
        $_SESSION['customer_first_name'] = $this->data['customers_firstname'];
        $_SESSION['customer_last_name'] = $this->data['customers_lastname'];
        $_SESSION['customer_default_address_id'] = (int) $this->data['customers_default_address_id'];
        $_SESSION['customer_country_id'] = (int) $this->data['country_id'];
        $_SESSION['customer_zone_id'] = (int) $this->data['zone_id'];
        $_SESSION['customers_authorization'] = (int) $this->data['customers_authorization'];
        // @TODO - should we add $this->data to a session var, and replace numerous other lookups?
        if ($restore_cart) {
            $_SESSION['cart']->restore_contents();
        }
        // fire any notifiers
        return true;
    }
    /**
     * Clears any existing password reset-tokens for the specified customers_id.
     * @since ZC v2.2.0
     */
    protected function clear_password_reset_tokens(int $customers_id): void
    {
        global $db;
        $sql = 'DELETE FROM ' . TABLE_CUSTOMER_PASSWORD_RESET_TOKENS . '
              WHERE customer_id = :customerID';
        $sql = $db->bind_vars($sql, ':customerID', $customers_id, 'integer');
        $db->Execute($sql);
    }
    /**
     * @since ZC v2.2.0
     */
    public static function set_welcome_email_sent(int $customers_id): void
    {
        global $db;
        $sql = 'UPDATE ' . TABLE_CUSTOMERS . '
                SET welcome_email_sent = 1
              WHERE customers_id = :customerID';
        $sql = $db->bind_vars($sql, ':customerID', $customers_id, 'integer');
        $db->Execute($sql, 1);
    }
    /**
     * Clears any existing account-authorization tokens for the current customer.
     * @since ZC v2.2.0
     */
    protected static function clear_auth_tokens(int $customers_id): void
    {
        global $db;
        $sql = 'DELETE FROM ' . TABLE_CUSTOMERS_AUTH_TOKENS . '
              WHERE customers_id = :customerID';
        $sql = $db->bind_vars($sql, ':customerID', $customers_id, 'integer');
        $db->Execute($sql);
    }
    /**
     * @since ZC v2.2.0
     */
    public static function get_auth_token_minutes_valid(): int
    {
        $token_valid_minutes = (int) CUSTOMERS_ACTIVATION_TOKEN_MINUTES_VALID;
        if ($token_valid_minutes < 1 || $token_valid_minutes > 1440) {
            return 60;
        }
        return $token_valid_minutes;
    }
    /**
     * @since ZC v2.2.0
     */
    public function refresh_customer_authorization(): false|array
    {
        if (empty($this->customer_id)) {
            return false;
        }
        global $db;
        $sql = 'SELECT customers_authorization
               FROM ' . TABLE_CUSTOMERS . '
              WHERE customers_id = :customersID
              LIMIT 1';
        $sql = $db->bind_vars($sql, ':customersID', $this->customer_id, 'integer');
        $check_customer = $db->execute_no_cache($sql);
        if ($check_customer->EOF) {
            return false;
        }
        $this->data['customers_authorization'] = (int) $check_customer->fields['customers_authorization'];
        $_SESSION['customers_authorization'] = $this->data['customers_authorization'];
        return $this->data;
    }
    /**
     * @since ZC v2.2.0
     */
    public function get_auth_token_info(): array|false
    {
        if (empty($this->data) || $this->data['activation_required'] === 0) {
            return false;
        }
        global $db;
        $sql = 'SELECT *
               FROM ' . TABLE_CUSTOMERS_AUTH_TOKENS . '
              WHERE customers_id = :customer_id
              LIMIT 1';
        $sql = $db->bind_vars($sql, ':customer_id', $this->customer_id, 'integer');
        $result = $db->execute_no_cache($sql);
        return $result->EOF ? false : $result->fields;
    }
    /**
     * @since ZC v2.2.0
     */
    public static function get_auth_token_valid(string $reset_token): array|false
    {
        global $db;
        $token_valid_minutes = self::get_auth_token_minutes_valid();
        $sql = 'SELECT cat.*
               FROM   ' . TABLE_CUSTOMERS_AUTH_TOKENS . ' cat
                    INNER JOIN ' . TABLE_CUSTOMERS . " c\n                        ON c.customers_id = cat.customers_id\n              WHERE cat.token = :reset_token\n                AND cat.created_at > DATE_SUB(CURRENT_TIMESTAMP, INTERVAL {$token_valid_minutes} MINUTE)\n              LIMIT 1";
        $sql = $db->bind_vars($sql, ':reset_token', $reset_token, 'string');
        $result = $db->execute_no_cache($sql);
        if ($result->EOF) {
            return false;
        }
        return $result->fields;
    }
    /**
     * @since ZC v2.2.0
     */
    public function create_auth_token(): string|false
    {
        if (empty($this->data) || CUSTOMERS_ACTIVATION_REQUIRED === 'false') {
            return false;
        }
        global $db;
        $length = (int) CUSTOMERS_ACTIVATION_TOKEN_LENGTH;
        if ($length < 12 || $length > 100) {
            // under 12 is impractical; over 100 is too large for db field
            $length = 24;
        }
        $token = zen_create_random_value($length);
        $sql = 'DELETE FROM ' . TABLE_CUSTOMERS_AUTH_TOKENS . ' WHERE customers_id = :customerID';
        $sql = $db->bind_vars($sql, ':customerID', $this->customer_id, 'integer');
        $db->Execute($sql);
        $sql = 'INSERT INTO ' . TABLE_CUSTOMERS_AUTH_TOKENS . ' (customers_id, email_address, token) VALUES (:customerID, :emailAddress, :token)';
        $sql = $db->bind_vars($sql, ':token', $token, 'string');
        $sql = $db->bind_vars($sql, ':customerID', $this->customer_id, 'integer');
        $sql = $db->bind_vars($sql, ':emailAddress', $this->data['customers_email_address'], 'string');
        $db->Execute($sql);
        return $token;
    }
    /**
     * Return whether the current customer session is associated with a guest-checkout process.
     * @since ZC v1.5.8
     */
    public function is_in_guest_checkout(): bool
    {
        $in_guest_checkout = false;
        $this->notify('NOTIFY_ZEN_IN_GUEST_CHECKOUT', null, $in_guest_checkout);
        return (bool) $in_guest_checkout;
    }
    /**
     * @since ZC v1.5.8
     */
    public function customer_exists_in_database(?int $customer_id = null): bool
    {
        global $db;
        if (empty($customer_id)) {
            $customer_id = $this->customer_id;
        }
        if (empty($customer_id)) {
            return false;
        }
        $sql = 'SELECT customers_id
               FROM ' . TABLE_CUSTOMERS . '
              WHERE customers_id = ' . (int) $customer_id;
        $result = $db->Execute($sql, 1);
        return !$result->EOF;
    }
    /**
     * @since ZC v1.5.8
     */
    protected function load(?int $customer_id = null): bool
    {
        global $db;
        if (empty($customer_id)) {
            $customer_id = $this->customer_id;
        }
        if (empty($customer_id)) {
            $this->data = [];
            return false;
        }
        $data_ok = $this->load_base_customer_info($customer_id);
        if ($data_ok === false) {
            return false;
        }
        // load address info, while also correcting for missing default address_book id
        $addresses = $this->get_formatted_address_book_list($customer_id);
        $found_default_address_id = false;
        $first_address = null;
        foreach ($addresses as $address) {
            if (empty($first_address)) {
                $first_address = $address['address_book_id'];
            }
            if ($address['address_book_id'] == $this->data['customers_default_address_id']) {
                $this->data += $address['address'];
                $found_default_address_id = true;
                break;
            }
        }
        if (!$found_default_address_id && !empty($first_address)) {
            $this->set_default_address_book_id($first_address);
            foreach ($addresses as $address) {
                if ($address['address_book_id'] === $first_address) {
                    $this->data += $address['address'];
                    break;
                }
            }
        }
        // keep this info so we don't have to query it again
        $this->data['addresses'] = $addresses;
        $sql = 'SELECT COUNT(*) AS number_of_reviews
               FROM ' . TABLE_REVIEWS . '
              WHERE customers_id = ' . (int) $customer_id;
        $result = $db->Execute($sql);
        $this->data['number_of_reviews'] = (int) $result->fields['number_of_reviews'];
        if (IS_ADMIN_FLAG) {
            $this->data['number_of_orders'] = $this->count_customers_previous_orders();
            // only calculating this on the Admin side, for performance reasons
            if ($this->data['number_of_orders']) {
                $this->data['lifetime_value'] = $this->get_lifetime_value();
            }
        } else {
            $this->data['lifetime_value'] = null;
            $this->data['number_of_orders'] = $this->get_number_of_orders();
        }
        $this->get_pricing_group_association();
        $this->notify('NOTIFY_CUSTOMER_DATA_LOADED', $this->data, $this->data);
        $this->convert_data_to_ints();
        return true;
    }
    /**
     * @since ZC v2.2.0
     */
    protected function load_base_customer_info(int $customer_id): bool
    {
        global $db;
        $sql = "SELECT c.*,\n                    CONCAT(customers_firstname,' ',LEFT(customers_lastname,1),'.') as name_with_initial,\n                    cgc.amount as gv_balance,\n                    customers_info_date_account_created AS date_account_created,\n                    customers_info_date_account_last_modified AS date_account_last_modified,\n                    customers_info_date_of_last_logon AS date_of_last_login,\n                    customers_info_number_of_logons AS number_of_logins\n               FROM " . TABLE_CUSTOMERS . ' c
                    LEFT JOIN ' . TABLE_CUSTOMERS_INFO . ' ci ON (c.customers_id = ci.customers_info_id)
                    LEFT JOIN ' . TABLE_COUPON_GV_CUSTOMER . ' cgc ON (c.customers_id = cgc.customer_id)
              WHERE c.customers_id = ' . $customer_id . '
              LIMIT 1';
        $result = $db->execute_no_cache($sql);
        $this->data = [];
        if ($result->EOF) {
            return false;
        }
        foreach ($result->fields as $key => $value) {
            if ($key === 'customers_password') {
                continue;
            }
            $this->data[$key] = $value;
        }
        $this->convert_data_to_ints();
        return true;
    }
    /**
     * @since ZC v2.2.0
     */
    protected function convert_data_to_ints(): void
    {
        // treat these as integers even though they (may have) come from the db as strings
        $ints = ['customers_id', 'customers_default_address_id', 'customers_newsletter', 'customers_group_pricing', 'customers_authorization', 'activation_required', 'number_of_logins', 'address_book_id', 'zone_id', 'country_id', 'number_of_reviews', 'number_of_orders'];
        foreach ($ints as $key) {
            if (isset($this->data[$key])) {
                $this->data[$key] = (int) $this->data[$key];
            }
        }
    }
    /**
     * Return the count of the current customer's previous orders.
     * @since ZC v1.5.8
     */
    protected function count_customers_previous_orders(): int
    {
        global $db;
        $orders = $db->Execute('SELECT COUNT(*) AS count
               FROM ' . TABLE_ORDERS . '
              WHERE customers_id = ' . (int) $this->customer_id);
        return (int) $orders->fields['count'];
    }
    /**
     * Retrieve the current customer's lifetime value,
     * the sum of all previously-placed orders.
     * @since ZC v1.5.8
     */
    protected function get_lifetime_value(): float|int
    {
        global $db, $currencies;
        $lifetime_value = 0;
        $sql = 'SELECT o.orders_id, o.date_purchased, o.order_total AS order_total_raw, o.currency, o.currency_value, o.language_code
               FROM ' . TABLE_ORDERS . ' o
              WHERE customers_id = ' . (int) $this->customer_id . '
              ORDER BY date_purchased DESC';
        $results = $db->Execute($sql);
        $last_order = null;
        foreach ($results as $result) {
            if (null === $last_order) {
                $last_order = ['date_purchased' => $result['date_purchased'], 'order_total' => $currencies->format($result['order_total_raw'], false, $result['currency'], $result['currency_value']), 'order_total_raw' => $result['order_total_raw'], 'currency' => $result['currency'], 'currency_value' => $result['currency_value'], 'language_code' => $result['language_code']];
            }
            $lifetime_value += $result['order_total_raw'] * $result['currency_value'];
        }
        $this->data['last_order'] = $last_order;
        $this->data['lifetime_value'] = $lifetime_value;
        return $lifetime_value;
    }
    /**
     * Add group-pricing details to the $this->data array
     * @since ZC v1.5.8
     */
    protected function get_pricing_group_association(): void
    {
        global $db;
        $sql = 'SELECT group_name, group_percentage
               FROM ' . TABLE_GROUP_PRICING . '
              WHERE group_id = ' . (int) $this->data['customers_group_pricing'];
        $result = $db->Execute($sql);
        if ($result->record_count()) {
            $this->data['pricing_group_name'] = $result->fields['group_name'];
            $this->data['pricing_group_discount_percentage'] = $result->fields['group_percentage'];
        } else {
            $this->data['pricing_group_name'] = defined('TEXT_NONE') ? TEXT_NONE : '';
            $this->data['pricing_group_discount_percentage'] = 0;
        }
        $this->notify('NOTIFY_CUSTOMER_PRICING_GROUP_LOADED', $this->data);
    }
    /**
     * Update customer record in db with default address-book id
     * @since ZC v1.5.8
     */
    protected function set_default_address_book_id(int $id): void
    {
        global $db;
        $sql = 'UPDATE ' . TABLE_CUSTOMERS . '
                SET customers_default_address_id = ' . $id . '
              WHERE customers_id = ' . (int) $this->customer_id;
        $db->Execute($sql);
        $this->data['customers_default_address_id'] = $id;
    }
    /**
     * @since ZC v1.5.8
     */
    public function is_banned(?int $customer_id = null): bool
    {
        $banned_status = false;
        if (!empty($customer_id) || empty($this->data)) {
            $this->load($customer_id);
        }
        if ((int) $this->data['customers_authorization'] === self::AUTH_BANNED) {
            // Banned status is 4
            $banned_status = true;
        }
        $this->notify('NOTIFY_CUSTOMER_CHECK_IF_BANNED', $this->data, $banned_status);
        return $banned_status;
    }
    /**
     * @since ZC v1.5.8
     */
    public function ban_customer(): void
    {
        $proceed_with_ban = true;
        $reset_shopping_session_and_basket = true;
        $this->notify('NOTIFY_BAN_CUSTOMER', $this->data, $proceed_with_ban, $reset_shopping_session_and_basket);
        if ($proceed_with_ban) {
            $this->set_customer_authorization_status(self::AUTH_BANNED);
            if ($reset_shopping_session_and_basket) {
                $this->reset_customer_cart();
            } else {
                $this->force_logout();
            }
            $this->data = [];
        }
    }
    /**
     * @since ZC v1.5.8
     */
    public function set_customer_authorization_status(int $status): array
    {
        global $db;
        $activation_required = 0;
        if ($status !== self::AUTH_OK && CUSTOMERS_ACTIVATION_REQUIRED === 'true') {
            $activation_required = (int) ($status === self::AUTH_NO_PURCHASE);
        }
        $sql = 'UPDATE ' . TABLE_CUSTOMERS . '
                SET customers_authorization = ' . $status . ",\n                    activation_required = {$activation_required}\n              WHERE customers_id = " . (int) $this->customer_id;
        $db->Execute($sql, 1);
        $this->data['customers_authorization'] = $status;
        $this->data['activation_required'] = $activation_required;
        self::clear_auth_tokens((int) $this->customer_id);
        return $this->data;
    }
    /**
     * Unconditionally authorizes the specified customer, returning an array containing
     * the customer's current information, if that customer is present in the database.
     *
     * @since ZC v1.5.8
     */
    public static function authorize_customer(int $customers_id): array
    {
        global $db;
        $sql = 'UPDATE ' . TABLE_CUSTOMERS . '
                SET customers_authorization = ' . self::AUTH_OK . ',
                    activation_required = 0
              WHERE customers_id = ' . $customers_id;
        $db->Execute($sql, 1);
        self::clear_auth_tokens($customers_id);
        $customer = $db->execute_no_cache('SELECT *
               FROM ' . TABLE_CUSTOMERS . '
              WHERE customers_id = ' . $customers_id . '
              LIMIT 1');
        return $customer->EOF ? [] : $customer->fields;
    }
    /**
     * @since ZC v1.5.8
     */
    public function reset_customer_cart(): void
    {
        global $db;
        $db->Execute('DELETE FROM ' . TABLE_CUSTOMERS_BASKET . ' WHERE customers_id = ' . $this->customer_id);
        $db->Execute('DELETE FROM ' . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . ' WHERE customers_id = ' . $this->customer_id);
        $_SESSION['cart']->reset(true);
        $this->force_logout();
    }
    /**
     * @since ZC v1.5.8
     */
    public function force_logout(): bool
    {
        global $db;
        if ($this->is_same_as_logged_in()) {
            // clean out whos_online for this user's session
            $db->Execute('DELETE FROM ' . TABLE_WHOS_ONLINE . ' WHERE customer_id = ' . (int) $_SESSION['customer_id']);
            // @TODO - kill actual session from sessionhandler too? (eg: really boot them out)
            unset($_SESSION['customer_id']);
            return true;
        }
        return false;
    }
    /**
     * @since ZC v1.5.8
     */
    public function get_address_book_entries(?int $customer_id = null): Query_Factory_Result|array
    {
        global $db;
        if (empty($customer_id)) {
            $customer_id = $this->customer_id;
        }
        if (empty($customer_id)) {
            return [];
        }
        $sql = 'SELECT c.*, ab.*
               FROM ' . TABLE_ADDRESS_BOOK . ' ab
                    LEFT JOIN ' . TABLE_CUSTOMERS . ' c USING (customers_id)
              WHERE customers_id = ' . (int) $customer_id;
        return $db->Execute($sql);
    }
    /**
     * @since ZC v1.5.8
     */
    public function get_number_of_address_book_entries(?int $customer_id = null): int
    {
        if (empty($customer_id)) {
            $customer_id = $this->customer_id;
        }
        if (empty($customer_id)) {
            return 0;
        }
        return count($this->get_address_book_entries());
    }
    /**
     * @since ZC v1.5.8
     */
    public function get_formatted_address_book_list(?int $customer_id = null): array
    {
        global $db;
        if (empty($customer_id)) {
            $customer_id = $this->customer_id;
        }
        if (empty($customer_id)) {
            return [];
        }
        $sql = 'SELECT ab.*,
                    entry_firstname AS firstname, entry_lastname AS lastname,
                    entry_company AS company, entry_street_address AS street_address,
                    entry_suburb AS suburb, entry_city AS city, entry_postcode AS postcode,
                    entry_state AS state,
                    entry_zone_id AS zone_id,
                    zone_name, zone_code AS zone_iso,
                    entry_country_id AS country_id,
                    countries_name AS country_name,
                    countries_iso_code_3 AS country_iso,
                    countries_iso_code_2 AS country_iso_2
               FROM ' . TABLE_ADDRESS_BOOK . ' ab
                    INNER JOIN ' . TABLE_COUNTRIES . ' c ON (ab.entry_country_id = c.countries_id)
                    LEFT JOIN ' . TABLE_ZONES . ' z ON (ab.entry_zone_id = z.zone_id AND z.zone_country_id = c.countries_id)
              WHERE customers_id = :customersID
              ORDER BY firstname, lastname';
        $sql = $db->bind_vars($sql, ':customersID', $customer_id, 'integer');
        $results = $db->Execute($sql);
        $address_array = [];
        foreach ($results as $result) {
            $format_id = zen_get_address_format_id((int) $result['country_id']);
            if (empty($result['state']) && !empty($result['zone_name'])) {
                $result['state'] = $result['zone_name'];
            }
            $address_array[] = ['firstname' => $result['firstname'], 'lastname' => $result['lastname'], 'company' => $result['company'], 'address_book_id' => $result['address_book_id'], 'country_id' => $result['country_id'], 'country_iso' => $result['country_iso'], 'country_name' => $result['country_name'], 'format_id' => $format_id, 'address' => $result];
        }
        return $address_array;
    }
    /**
     * @since ZC v1.5.8
     */
    public function get_order_history(int $max_number_to_return = 0, &$returned_history_split = null): array
    {
        $language = $_SESSION['languages_id'];
        global $db, $currencies;
        $sql = 'SELECT o.orders_id, o.date_purchased, o.delivery_name,
                    o.order_total, o.currency, o.currency_value,
                    o.delivery_country, o.billing_name, o.billing_country,
                    o.orders_status, s.orders_status_name,
                    o.language_code
              FROM ' . TABLE_ORDERS . ' o
                    LEFT JOIN ' . TABLE_ORDERS_STATUS . ' s
                        ON s.orders_status_id = o.orders_status
                       AND s.language_id = :languagesID
              WHERE o.customers_id = :customersID
              ORDER BY orders_id DESC';
        $sql = $db->bind_vars($sql, ':customersID', $this->customer_id, 'integer');
        $sql = $db->bind_vars($sql, ':languagesID', $language, 'integer');
        if ($returned_history_split !== null) {
            $history_split = new Split_Page_Results($sql, $max_number_to_return);
            $returned_history_split = $history_split;
            $results = $db->Execute($history_split->sql_query);
        } else {
            $results = $db->Execute($sql, $max_number_to_return);
        }
        $orders_array = [];
        foreach ($results as $result) {
            if (!empty($result['delivery_name'])) {
                $order_type = defined('TEXT_ORDER_SHIPPED_TO') ? TEXT_ORDER_SHIPPED_TO : 'Shipped To:';
                $order_name = $result['delivery_name'];
                $order_country = $result['delivery_country'];
            } else {
                $order_type = defined('TEXT_ORDER_BILLED_TO') ? TEXT_ORDER_BILLED_TO : 'Billed To:';
                $order_name = $result['billing_name'];
                $order_country = $result['billing_country'];
            }
            $sql = 'SELECT COUNT(*) AS count
                   FROM ' . TABLE_ORDERS_PRODUCTS . '
                  WHERE orders_id = ' . (int) $result['orders_id'];
            $query_result = $db->Execute($sql);
            $products_count = $query_result->EOF ? 0 : $query_result->fields['count'];
            $orders_array[] = ['orders_id' => (int) $result['orders_id'], 'date_purchased' => $result['date_purchased'], 'order_type' => $order_type, 'order_name' => $order_name, 'order_country' => $order_country, 'orders_status_name' => $result['orders_status_name'] ?? sprintf(TEXT_UNKNOWN_ORDERS_STATUS_NAME, (int) $result['orders_status']), 'order_total' => $currencies->format($result['order_total'], true, $result['currency'], $result['currency_value']), 'order_total_raw' => $result['order_total'], 'currency' => $result['currency'], 'currency_value' => $result['currency_value'], 'language_code' => $result['language_code'], 'product_count' => $products_count];
        }
        return $orders_array;
    }
    /**
     * Used catalog-side in the My Account page(s)
     * @since ZC v1.5.8
     */
    public function get_number_of_orders(): int
    {
        if (!$this->is_logged_in) {
            return 0;
        }
        if ($this->is_in_guest_checkout) {
            return 0;
        }
        if (empty($this->customer_id)) {
            return 0;
        }
        global $db;
        $sql = 'SELECT COUNT(*) as total
               FROM ' . TABLE_ORDERS . '
              WHERE customers_id = ' . (int) $this->customer_id;
        $result = $db->Execute($sql);
        return $result->fields['total'];
    }
    /**
     * @since ZC v1.5.8
     */
    public function set_password(string $new_password): void
    {
        global $db;
        $sql = 'UPDATE ' . TABLE_CUSTOMERS . '
                SET customers_password = :password
              WHERE customers_id = :customersID';
        $sql = $db->bind_vars($sql, ':customersID', $this->customer_id, 'integer');
        $sql = $db->bind_vars($sql, ':password', zen_encrypt_password($new_password), 'string');
        $db->Execute($sql, 1);
        $sql = 'UPDATE ' . TABLE_CUSTOMERS_INFO . '
                SET customers_info_date_account_last_modified = now()
              WHERE customers_info_id = :customersID';
        $sql = $db->bind_vars($sql, ':customersID', $this->customer_id, 'integer');
        $db->Execute($sql, 1);
        $this->clear_password_reset_tokens($this->customer_id);
    }
    /**
     * @since ZC v2.2.0
     */
    public function set_password_using_email_address(string $new_password, string $email_address): void
    {
        global $db;
        $sql = 'UPDATE ' . TABLE_CUSTOMERS . '
                SET customers_password = :password
              WHERE customers_email_address = :emailAddress';
        $sql = $db->bind_vars($sql, ':emailAddress', $email_address, 'string');
        $sql = $db->bind_vars($sql, ':password', zen_encrypt_password($new_password), 'string');
        $db->Execute($sql, 1);
    }
    /**
     * Delete customer and all relations
     *
     * @param bool $forget_only Instead of delete, simply obfuscate address/name data
     * @since ZC v1.5.8
     */
    public function delete(bool $delete_reviews = false, bool $forget_only = false): void
    {
        global $db;
        if ($delete_reviews) {
            $reviews = $db->Execute('SELECT reviews_id
                   FROM ' . TABLE_REVIEWS . '
                  WHERE customers_id = ' . (int) $this->customer_id);
            foreach ($reviews as $review) {
                $db->Execute('DELETE FROM ' . TABLE_REVIEWS_DESCRIPTION . '
                      WHERE reviews_id = ' . (int) $review['reviews_id']);
            }
            $db->Execute('DELETE FROM ' . TABLE_REVIEWS . "\n                  WHERE customers_id = '" . (int) $this->customer_id . "'");
        } else {
            $fields = 'customers_id = null';
            if ($forget_only) {
                $text_anonymous = defined('DB_TEXT_ANONYMOUS') ? zen_db_input(constant('DB_TEXT_ANONYMOUS')) : 'anonymous';
                $fields = "customers_name = '" . $text_anonymous . "'";
            }
            $db->Execute('UPDATE ' . TABLE_REVIEWS . '
                    SET ' . $fields . '
                  WHERE customers_id = ' . (int) $this->customer_id);
        }
        $text_deleted = defined('DB_TEXT_DELETED') ? zen_db_input(constant('DB_TEXT_DELETED')) : 'deleted';
        if ($forget_only) {
            $db->Execute('UPDATE ' . TABLE_ADDRESS_BOOK . "\n                    SET entry_gender = '',\n                        entry_company = '',\n                        entry_firstname = '',\n                        entry_lastname = '" . $text_deleted . "',\n                        entry_street_address = '" . $text_deleted . "',\n                        entry_suburb = ''\n                  WHERE customers_id = " . (int) $this->customer_id);
            $db->Execute('UPDATE ' . TABLE_CUSTOMERS . "\n                    SET customers_gender = '',\n                        customers_firstname = '" . $text_deleted . "',\n                        customers_lastname = '" . $text_deleted . ' ' . date('Y-m-d') . "',\n                        customers_email_address = '" . $text_deleted . "',\n                        customers_dob = '0001-01-01 00:00:00',\n                        customers_newsletter = null,\n                        customers_nick = '',\n                        customers_paypal_payerid = '',\n                        customers_secret = '',\n                        customers_password = '',\n                        customers_telephone = '',\n                        registration_ip = '',\n                        last_login_ip = '',\n                        customers_fax = ''\n                  WHERE customers_id = " . (int) $this->customer_id);
        } else {
            $db->Execute('DELETE FROM ' . TABLE_ADDRESS_BOOK . '
                  WHERE customers_id = ' . (int) $this->customer_id);
            $db->Execute('DELETE FROM ' . TABLE_CUSTOMERS . '
                  WHERE customers_id = ' . (int) $this->customer_id);
            $db->Execute('DELETE FROM ' . TABLE_CUSTOMERS_INFO . '
                  WHERE customers_info_id = ' . (int) $this->customer_id);
        }
        $db->Execute('DELETE FROM ' . TABLE_CUSTOMERS_BASKET . '
              WHERE customers_id = ' . (int) $this->customer_id);
        $db->Execute('DELETE FROM ' . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . '
              WHERE customers_id = ' . (int) $this->customer_id);
        $db->Execute('DELETE FROM ' . TABLE_WHOS_ONLINE . '
              WHERE customer_id = ' . (int) $this->customer_id);
        $db->Execute('DELETE FROM ' . TABLE_PRODUCTS_NOTIFICATIONS . '
              WHERE customers_id = ' . (int) $this->customer_id);
        $this->clear_password_reset_tokens($this->customer_id);
        self::clear_auth_tokens($this->customer_id);
        $this->notify('NOTIFY_CUSTOMER_AFTER_RECORD_DELETED', (int) $this->customer_id);
        zen_record_admin_activity('Customer with customer ID ' . (int) $this->customer_id . ' deleted.', 'warning');
    }
    /**
     * @since ZC v1.5.8
     */
    public function create(array $data): array
    {
        global $db;
        $this->notify('NOTIFY_MODULE_CREATE_ACCOUNT_ADDING_CUSTOMER_RECORD', null, $data);
        $activation_required = (int) (CUSTOMERS_ACTIVATION_REQUIRED === 'true');
        $sql_data_array = [['fieldName' => 'customers_firstname', 'value' => $data['firstname'], 'type' => 'stringIgnoreNull'], ['fieldName' => 'customers_lastname', 'value' => $data['lastname'], 'type' => 'stringIgnoreNull'], ['fieldName' => 'customers_email_address', 'value' => $data['email_address'], 'type' => 'stringIgnoreNull'], ['fieldName' => 'customers_nick', 'value' => $data['nick'], 'type' => 'stringIgnoreNull'], ['fieldName' => 'customers_telephone', 'value' => $data['telephone'], 'type' => 'stringIgnoreNull'], ['fieldName' => 'customers_fax', 'value' => $data['fax'], 'type' => 'stringIgnoreNull'], ['fieldName' => 'customers_newsletter', 'value' => $data['newsletter'], 'type' => 'integer'], ['fieldName' => 'customers_email_format', 'value' => $data['email_format'], 'type' => 'stringIgnoreNull'], ['fieldName' => 'customers_default_address_id', 'value' => 0, 'type' => 'integer'], ['fieldName' => 'customers_password', 'value' => zen_encrypt_password($data['password']), 'type' => 'stringIgnoreNull'], ['fieldName' => 'customers_authorization', 'value' => $data['customers_authorization'], 'type' => 'integer'], ['fieldName' => 'activation_required', 'value' => $activation_required, 'type' => 'integer'], ['fieldName' => 'welcome_email_sent', 'value' => 0, 'type' => 'integer'], ['fieldName' => 'registration_ip', 'value' => $data['ip_address'], 'type' => 'string'], ['fieldName' => 'last_login_ip', 'value' => $data['ip_address'], 'type' => 'string']];
        if (CUSTOMERS_REFERRAL_STATUS === '2' && !empty($data['customers_referral'])) {
            $sql_data_array[] = ['fieldName' => 'customers_referral', 'value' => $data['customers_referral'], 'type' => 'stringIgnoreNull'];
        }
        if (ACCOUNT_GENDER === 'true') {
            $sql_data_array[] = ['fieldName' => 'customers_gender', 'value' => $data['gender'], 'type' => 'stringIgnoreNull'];
        }
        if (ACCOUNT_DOB === 'true') {
            $dob = empty($data['dob']) || $data['dob'] === '0001-01-01 00:00:00' ? '0001-01-01 00:00:00' : zen_date_raw($data['dob']);
            $sql_data_array[] = ['fieldName' => 'customers_dob', 'value' => $dob, 'type' => 'date'];
        }
        $db->perform(TABLE_CUSTOMERS, $sql_data_array);
        $this->customer_id = $db->insert_ID();
        $customer_id = $this->customer_id;
        if ($activation_required) {
            $this->create_auth_token();
        }
        $this->notify('NOTIFY_MODULE_CREATE_ACCOUNT_ADDED_CUSTOMER_RECORD', array_merge(['customer_id' => $customer_id], $sql_data_array));
        $sql_data_array = [['fieldName' => 'customers_id', 'value' => $customer_id, 'type' => 'integer'], ['fieldName' => 'entry_firstname', 'value' => $data['firstname'], 'type' => 'stringIgnoreNull'], ['fieldName' => 'entry_lastname', 'value' => $data['lastname'], 'type' => 'stringIgnoreNull'], ['fieldName' => 'entry_street_address', 'value' => $data['street_address'], 'type' => 'stringIgnoreNull'], ['fieldName' => 'entry_postcode', 'value' => $data['postcode'], 'type' => 'stringIgnoreNull'], ['fieldName' => 'entry_city', 'value' => $data['city'], 'type' => 'stringIgnoreNull'], ['fieldName' => 'entry_country_id', 'value' => $data['country'], 'type' => 'integer']];
        if (ACCOUNT_GENDER === 'true') {
            $sql_data_array[] = ['fieldName' => 'entry_gender', 'value' => $data['gender'], 'type' => 'stringIgnoreNull'];
        }
        if (ACCOUNT_COMPANY === 'true') {
            $sql_data_array[] = ['fieldName' => 'entry_company', 'value' => $data['company'], 'type' => 'stringIgnoreNull'];
        }
        if (ACCOUNT_SUBURB === 'true') {
            $sql_data_array[] = ['fieldName' => 'entry_suburb', 'value' => $data['suburb'], 'type' => 'stringIgnoreNull'];
        }
        if (ACCOUNT_STATE === 'true') {
            if ($data['zone_id'] > 0) {
                $sql_data_array[] = ['fieldName' => 'entry_zone_id', 'value' => $data['zone_id'], 'type' => 'integer'];
                $sql_data_array[] = ['fieldName' => 'entry_state', 'value' => '', 'type' => 'stringIgnoreNull'];
            } else {
                $sql_data_array[] = ['fieldName' => 'entry_zone_id', 'value' => 0, 'type' => 'integer'];
                $sql_data_array[] = ['fieldName' => 'entry_state', 'value' => $data['state'], 'type' => 'stringIgnoreNull'];
            }
        }
        $db->perform(TABLE_ADDRESS_BOOK, $sql_data_array);
        $address_id = $db->insert_ID();
        $this->notify('NOTIFY_MODULE_CREATE_ACCOUNT_ADDED_ADDRESS_BOOK_RECORD', array_merge(['address_id' => $address_id], $sql_data_array));
        $sql = 'UPDATE ' . TABLE_CUSTOMERS . '
                SET customers_default_address_id = ' . (int) $address_id . '
              WHERE customers_id = ' . (int) $customer_id;
        $db->Execute($sql, 1);
        $sql = 'INSERT INTO ' . TABLE_CUSTOMERS_INFO . '
                (customers_info_id, customers_info_number_of_logons,
                 customers_info_date_account_created, customers_info_date_of_last_logon)
             VALUES
                (' . (int) $customer_id . ', 1, now(), now())';
        $db->Execute($sql);
        $this->load($customer_id);
        return $this->data;
    }
    /**
     * @since ZC v2.2.0
     */
    public function update(array $sql_data_array): array
    {
        global $db;
        $db->perform(TABLE_CUSTOMERS, $sql_data_array, 'update', 'customers_id = ' . (int) $this->customer_id . ' LIMIT 1');
        $db->Execute('UPDATE ' . TABLE_CUSTOMERS_INFO . '
                SET customers_info_date_account_last_modified = now()
              WHERE customers_info_id = ' . (int) $this->customer_id . '
              LIMIT 1');
        $this->load_base_customer_info($this->customer_id);
        return $this->data;
    }
    /**
     * @since ZC v2.2.0
     */
    public function update_primary_address(array $sql_data_array): void
    {
        $this->update_address($sql_data_array, (int) $this->data['customers_default_address_id']);
    }
    /**
     * @since ZC v2.2.0
     */
    public function update_address(array $sql_data_array, int $address_book_id): void
    {
        global $db;
        $db->perform(TABLE_ADDRESS_BOOK, $sql_data_array, 'update', 'customers_id = ' . (int) $this->customer_id . ' AND address_book_id = ' . $address_book_id . ' LIMIT 1');
    }
    // @TODO - add method for deleting duplicate identical address_book records?
}