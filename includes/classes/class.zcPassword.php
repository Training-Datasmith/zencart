<?php

declare(strict_types=1);
/**
 * File contains just the zcPassword class
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
/**
 * class zcPassword
 *
 * helper class for managing password hashing for different PHP versions
 *
 * Updates admin/customer tables on successful login
 *
 * @since ZC v1.5.3
 */
class zcPassword extends base
{
    /**
     *
     * @var $instance object
     */
    protected static $instance;
    /**
     * enforce singleton
     *
     * @param string $phpVersion
     * @since ZC v1.5.3
     */
    public static function getInstance($phpVersion)
    {
        if (! self::$instance) {
            $class = self::class;
            self::$instance = new $class($phpVersion);
        }
        return self::$instance;
    }
    /**
     * constructor
     */
    public function __construct()
    {
    }
    /**
     * Determine the password type
     *
     * Legacy passwords were hash:salt with a salt of length 2
     * php < 5.3.7 updated passwords are hash:salt with salt of length > 2
     * php >= 5.3.7 passwords are BMCF format
     *
     * @param string $encryptedPassword
     * @since ZC v1.5.3
     */
    public function detectPasswordType($encryptedPassword): string
    {
        $type = 'unknown';
        $tmp = explode(':', $encryptedPassword);
        if (count($tmp) == 2) {
            if (strlen($tmp [1]) > 2) {
                $type = 'compatSha256';
            } elseif (strlen($tmp [1]) == 2) {
                $type = 'oldMd5';
            }
        }
        return $type;
    }
    /**
     * validate a password where format is unknown
     *
     * @param string $plain
     * @param string $encrypted
     * @return boolean
     * @since ZC v1.5.3
     */
    public function validatePassword($plain, $encrypted)
    {
        $type = $this->detectPasswordType($encrypted);
        if ($type != 'unknown') {
            $method = 'validatePassword' . ucfirst($type);
            return $this->{$method}($plain, $encrypted);
        }
        return password_verify($plain, $encrypted);
    }
    /**
     * validate a legacy md5 type password
     *
     * @param string $encrypted
     * @since ZC v1.5.3
     */
    public function validatePasswordOldMd5(string $plain, $encrypted): bool
    {
        if (zen_not_null($plain) && zen_not_null($encrypted)) {
            $stack = explode(':', $encrypted);
            if (sizeof($stack) != 2) {
                return false;
            }
            if (hash('md5', $stack [1] . $plain) == $stack [0]) {
                return true;
            }
        }
        return false;
    }
    /**
     * validate a SHA256 type password
     *
     * @param string $encrypted
     * @since ZC v1.5.3
     */
    public function validatePasswordCompatSha256(string $plain, $encrypted): bool
    {
        if (zen_not_null($plain) && zen_not_null($encrypted)) {
            $stack = explode(':', $encrypted);
            if (sizeof($stack) != 2) {
                return false;
            }
            if (hash('sha256', $stack [1] . $plain) == $stack [0]) {
                return true;
            }
        }
        return false;
    }

    /**
     * Update a not logged in Admin password.
     *
     * @param string $plain
     * @param string $admin
     * @since ZC v1.5.3
     */
    public function updateNotLoggedInAdminPassword($plain, $admin): string
    {
        $this->confirmDbSchema('admin');
        global $db;
        $updatedPassword = password_hash($plain, PASSWORD_DEFAULT);
        $sql = 'UPDATE ' . TABLE_ADMIN . '
              SET admin_pass = :password:
              WHERE admin_name = :adminName:';

        $sql = $db->bindVars($sql, ':adminName:', $admin, 'string');
        $sql = $db->bindVars($sql, ':password:', $updatedPassword, 'string');
        $db->Execute($sql);
        return $updatedPassword;
    }
    /**
     * Ensure db schema has been updated to support the required password lengths
     * @param string $mode
     * @since ZC v1.5.3
     */
    public function confirmDbSchema($mode = ''): void
    {
        global $db;
        if ($mode == '' || $mode == 'admin') {
            $sql = 'ALTER TABLE ' . TABLE_ADMIN . " MODIFY admin_pass VARCHAR( 255 ) NOT NULL DEFAULT ''";
            $db->Execute($sql);
            $sql = 'ALTER TABLE ' . TABLE_ADMIN . " MODIFY prev_pass1 VARCHAR( 255 ) NOT NULL DEFAULT ''";
            $db->Execute($sql);
            $sql = 'ALTER TABLE ' . TABLE_ADMIN . " MODIFY prev_pass2 VARCHAR( 255 ) NOT NULL DEFAULT ''";
            $db->Execute($sql);
            $sql = 'ALTER TABLE ' . TABLE_ADMIN . " MODIFY prev_pass3 VARCHAR( 255 ) NOT NULL DEFAULT ''";
            $db->Execute($sql);
            $sql = 'ALTER TABLE ' . TABLE_ADMIN . " MODIFY reset_token VARCHAR( 255 ) NOT NULL DEFAULT ''";
            $db->Execute($sql);
        }
        if ($mode == '' || $mode == 'customer') {
            $found = false;
            $sql = 'show fields from ' . TABLE_CUSTOMERS;
            $result = $db->Execute($sql);
            while (!$result->EOF && !$found) {
                if ($result->fields['Field'] == 'customers_password' && strtoupper((string) $result->fields['Type']) == 'VARCHAR(255)') {
                    $found = true;
                }
                $result->MoveNext();
            }
            if (!$found) {
                $sql = 'ALTER TABLE ' . TABLE_CUSTOMERS . " MODIFY customers_password VARCHAR( 255 ) NOT NULL DEFAULT ''";
                $db->Execute($sql);
            }
        }
    }
}
