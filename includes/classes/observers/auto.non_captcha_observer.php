<?php

declare (strict_types=1);
/**
 * Designed for v1.5.7+
 *
 * Observer class used to detect spam input
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @copyright Portions Copyright 2017-2019 CowboyGeek.com
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 * @since ZC v1.5.7
 */
class Zc_Observer_Non_Captcha_Observer extends base
{
    private string $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    public function __construct()
    {
        $this->attach($this, ['NOTIFY_NONCAPTCHA_CHECK', 'NOTIFY_CREATE_ACCOUNT_CAPTCHA_CHECK', 'NOTIFY_CONTACT_US_CAPTCHA_CHECK', 'NOTIFY_REVIEWS_WRITE_CAPTCHA_CHECK']);
        if (empty($_SESSION['antispam_fieldname'])) {
            $_SESSION['antispam_fieldname'] = $this->generate_random_string($this->chars, 10);
        }
        $GLOBALS['antiSpamFieldName'] = $_SESSION['antispam_fieldname'];
    }
    // This update method fires if no updateNotifyxxxxxx function is declared below to match the notifier hooks we're listening to
    /**
     * @since ZC v1.5.7
     */
    public function update(&$class, $event_id, $params_array): void
    {
        $this->test_url_spam();
        $this->test_anti_spam_fields();
    }
    /**
     * @since ZC v1.5.7
     */
    public function update_notify_contact_us_captcha_check(&$class, $event_id, $params_array): void
    {
        // sanitize the contact-us name field more aggressively
        $GLOBALS['name'] = zen_db_prepare_input(zen_sanitize_string($_POST['contactname'] ?? ''));
        // fire default tests
        $this->update($class, $event_id, $params_array);
    }
    /**
     * @since ZC v1.5.7
     */
    protected function test_anti_spam_fields()
    {
        if (!empty($_POST[$_SESSION['antispam_fieldname']]) || !empty($_POST['should_be_empty'])) {
            $GLOBALS['antiSpam'] = 'spam';
        }
    }
    /**
     * @since ZC v1.5.7
     */
    protected function generate_random_string($input, $strength = 16): string
    {
        $input_length = strlen((string) $input);
        $random_string = '';
        for ($i = 0; $i < $strength; $i++) {
            $random_character = $input[random_int(0, $input_length - 1)];
            $random_string .= $random_character;
        }
        return $random_string;
    }
    /**
     * @since ZC v1.5.7
     */
    protected function test_url_spam()
    {
        $test_string = '';
        // Simple regex to identify presence of an (unwanted) URL
        $regex_pattern = '~(https?|ftps?):/~';
        $fields = [
            'firstname',
            'lastname',
            'contactname',
            'company',
            'street_address',
            'suburb',
            'city',
            'state',
            'zone_country_id',
            'nick',
            'customers_referral',
            'telephone',
            'fax',
            'email_format',
            'to_name',
            'subject',
            'passwordhintA',
            'review_text',
            // comment-out if you actually want to allow URLs for this
            'enquiry',
        ];
        // prepare for inspection
        $array_found = false;
        foreach ($fields as $field) {
            if (!empty($_POST[$field])) {
                if (is_array($_POST[$field])) {
                    $array_found = true;
                    $_POST[$field] = '';
                } else {
                    $test_string .= $_POST[$field];
                }
            }
        }
        if ($array_found) {
            $GLOBALS['antiSpam'] = 'spam';
            return;
        }
        if (empty(trim($test_string))) {
            return;
        }
        $test_string = str_ireplace([HTTP_SERVER, HTTPS_SERVER], '', $test_string);
        // inspect
        if (preg_match($regex_pattern, $test_string)) {
            $GLOBALS['antiSpam'] = 'spam';
        }
    }
}