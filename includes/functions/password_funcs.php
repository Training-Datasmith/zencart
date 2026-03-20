<?php

declare (strict_types=1);
/**
 * password_funcs functions
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
/**
 * This function validates a plain text password with an encrypted password
 * @since ZC v1.0.3
 */
function zen_validate_password($plain, $encrypted, $user_ref = null)
{
    $zc_password = Zc_Password::get_instance(PHP_VERSION);
    return $zc_password->validate_password($plain, $encrypted);
}
/**
 * This function makes a new password from a plaintext password.
 * @param $plain
 * @since ZC v1.0.3
 */
function zen_encrypt_password($plain): string
{
    return password_hash((string) $plain, PASSWORD_DEFAULT);
}
/**
 * this function makes a sha256 password from a plaintext password.
 * @param $plain
 * @since ZC v1.5.3
 */
function zen_encrypt_password_new(string $plain): string
{
    $password = '';
    for ($i = 0; $i < 40; $i++) {
        $password .= zen_rand();
    }
    $salt = hash('sha256', $password);
    return hash('sha256', $salt . $plain) . ':' . $salt;
}
/**
 * @since ZC v1.0.3
 */
function zen_create_random_value(string $length, $type = 'mixed'): false|string
{
    if ($type != 'mixed' && $type != 'chars' && $type != 'digits') {
        return false;
    }
    $rand_value = '';
    while (strlen($rand_value) < $length) {
        if ($type == 'digits') {
            $char = zen_rand(0, 9);
        } else {
            $char = chr(zen_rand(0, 255));
        }
        if ($type == 'mixed') {
            if (preg_match('/^[a-z0-9]$/i', (string) $char)) {
                $rand_value .= $char;
            }
        } elseif ($type == 'chars') {
            if (preg_match('/^[a-z]$/i', (string) $char)) {
                $rand_value .= $char;
            }
        } elseif ($type == 'digits') {
            if (preg_match('/^[0-9]$/', (string) $char)) {
                $rand_value .= $char;
            }
        }
    }
    if ($type == 'mixed' && !preg_match('/^(?=.*[\w]+.*)(?=.*[\d]+.*)[\d\w]{' . $length . ',}$/', $rand_value)) {
        $rand_value .= zen_rand(0, 9);
    }
    return $rand_value;
}
/**
 * Returns entropy using a hash of various available methods for obtaining
 * random data.
 * The default hash method is "sha1" and the default size is 32.
 *
 * @param string $hash
 *          the hash method to use while generating the hash.
 * @param int $size
 *          the size of random data to use while generating the hash.
 * @return string the randomized salt
 * @since ZC v1.5.1
 */
function zen_get_entropy($hash = 'sha1', $size = 32): string
{
    $data = null;
    if (!in_array($hash, hash_algos())) {
        $hash = 'sha1';
    }
    if (!is_int($size)) {
        $size = (int) $size;
    }
    // Use openssl if available
    if (function_exists('openssl_random_pseudo_bytes')) {
        // echo('Attempting to create entropy using openssl');
        $entropy = openssl_random_pseudo_bytes($size, $strong);
        if ($strong) {
            $data = $entropy;
        }
        unset($strong, $entropy);
    }
    // Use mcrypt with /dev/urandom if available
    if ($data === null && function_exists('mcrypt_create_iv') && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        // echo('Attempting to create entropy using mcrypt');
        $entropy = mcrypt_create_iv($size, MCRYPT_DEV_URANDOM);
        if ($entropy !== false) {
            $data = $entropy;
        }
        unset($entropy);
    }
    if ($data === null) {
        // Fall back to using /dev/urandom if available
        $fp = @fopen('/dev/urandom', 'rb');
        if ($fp !== false) {
            // echo('Attempting to create entropy using /dev/urandom');
            $entropy = @fread($fp, $size);
            @fclose($fp);
            if (strlen($entropy) == $size) {
                $data = $entropy;
            }
            unset($fp, $entropy);
        }
    }
    // Final fallback (mixture of various methods)
    if ($data === null) {
        // echo('Attempting to create entropy using FINAL FALLBACK');
        if (!defined('DIR_FS_ROOT')) {
            define('DIR_FS_ROOT', DIR_FS_CATALOG);
        }
        $filename = DIR_FS_ROOT . 'includes/configure.php';
        $stat = @stat($filename);
        if ($stat === false) {
            $stat = ['microtime' => microtime()];
        }
        $stat['mt_rand'] = mt_rand();
        $stat['file_hash'] = hash_file($hash, $filename, true);
        // Attempt to get a random value on windows
        // http://msdn.microsoft.com/en-us/library/aa388176(VS.85).aspx
        if (@class_exists('COM')) {
            try {
                $CAPI_Util = new COM('CAPICOM.Utilities.1');
                $entropy = $CAPI_Util->get_random($size, 0);
                if ($entropy) {
                    // echo('Adding random data to entropy using CAPICOM.Utilities');
                    $stat['CAPICOM_Utilities_random'] = hash('md5', $entropy, true);
                }
                unset($CAPI_Util, $entropy);
            } catch (Exception) {
            }
        }
        // echo('Adding random data to entropy using file information and contents');
        @shuffle($stat);
        foreach ($stat as $value) {
            $data .= $value;
        }
        unset($filename, $value, $stat);
    }
    return hash($hash, $data);
}
/**
 * @since ZC v1.5.1
 */
function zen_create_PADSS_password($length = 8): string
{
    $chars_alpha = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $chars_num = '0123456789';
    $chars_mixed = $chars_alpha . $chars_num;
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $add_char = substr($chars_mixed, zen_pwd_rand(0, strlen($chars_mixed) - 1), 1);
        while (strpos($password, $add_char)) {
            $add_char = substr($chars_mixed, zen_pwd_rand(0, strlen($chars_mixed) - 1), 1);
        }
        $password .= $add_char;
    }
    if (!preg_match('/[0-9]/', $password)) {
        $add_char = substr($chars_num, zen_pwd_rand(0, strlen($chars_num) - 1), 1);
        $add_pos = zen_pwd_rand(0, strlen($password) - 1);
        $password[$add_pos] = $add_char;
    }
    return $password;
}
/**
 * @since ZC v1.5.1
 */
function zen_pwd_rand($min = 0, $max = 10): int
{
    static $seed;
    if (!isset($seed)) {
        $seed = zen_get_entropy();
    }
    $random = hash('sha1', zen_get_entropy() . $seed);
    $random .= hash('sha1', zen_get_entropy() . $random);
    $random = hash('sha1', $random);
    $random = substr($random, 0, 8);
    $value = abs(hexdec($random));
    $value = $min + ($max - $min + 1) * ($value / (4294967295 + 1));
    return abs(intval($value));
}