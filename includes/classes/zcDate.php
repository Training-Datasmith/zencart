<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Oct 22 Modified in v2.2.0 $
 * @since ZC v1.5.8
 */
class Zc_Date extends base
{
    protected $use_intl_date = false;
    protected $use_strftime = false;
    protected $locale;
    //- Only used when $this->useIntlDate is true
    protected $strftime2date;
    //- Only used when $this->useStrftime is false
    protected $strftime2intl;
    //- Only used when $this->useStrftime is false
    protected $debug = false;
    protected $date_object;
    // -----
    // Initial construction; initializes the conversion arrays and determines which PHP
    // base function will be used by the output method.
    //
    // The $zen_date_debug is a "soft" configuration setting that can be forced (defaults to false)
    // via the site's /includes/extra_datafiles/site_specific_overrides.php
    //
    public function __construct()
    {
        global $zen_date_debug;
        if (isset($zen_date_debug) && $zen_date_debug === true) {
            $this->debug = true;
        }
        if (version_compare(phpversion(), '8.1', '<')) {
            $this->use_strftime = true;
        } else {
            if (function_exists('datefmt_create')) {
                $this->use_intl_date = true;
            }
            $this->initialize_conversion_arrays();
        }
        $this->debug('zcDate construction: ' . PHP_EOL . var_export($this, true));
    }
    // -----
    // Initializes the class-based arrays that define the format conversions
    // from their strftime format (the input requirement) and the formats used
    // by either the 'date' function or the IntlDateFormatter class.
    //
    // Each array's keys start out as the strftime format and a key's value is the converted format.
    // These arrays are then converted into a 'from' and a 'to' array that's used by the
    // method convertFormat's processing (essentially a str_replace on the submitted format string).
    //
    // strftime reference: https://www.php.net/manual/en/function.strftime.php
    // date_format reference: https://www.php.net/manual/en/datetime.format.php
    // intl format reference: https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax
    //
    /**
     * @since ZC v1.5.8
     */
    protected function initialize_conversion_arrays()
    {
        $strftime2date = ['%a' => 'D', '%A' => 'l', '%b' => 'M', '%B' => 'F', '%d' => 'd', '%H' => 'H', '%k' => 'G', '%m' => 'm', '%M' => 'i', '%S' => 's', '%T' => 'H:i:s', '%x' => defined('DATE_FORMAT') ? DATE_FORMAT : 'm/d/Y', '%X' => 'H:i:s', '%y' => 'y', '%Y' => 'Y', '%z' => 'eP', '%Z' => 'T'];
        $this->strftime2date = ['from' => array_keys($strftime2date), 'to' => array_values($strftime2date)];
        if ($this->use_intl_date === true) {
            // -----
            // First, save the current locale; it's set by the main language file's (presumed) call to the
            // setlocale function.
            //
            $this->locale = setlocale(LC_TIME, '0');
            // -----
            // Using the current locale, retrieve the locale-specific 'short' date and time
            // formats.
            //
            $format = new Intl_Date_Formatter($this->locale, Intl_Date_Formatter::SHORT, Intl_Date_Formatter::NONE);
            $date_short = $format->get_pattern();
            $format = new Intl_Date_Formatter($this->locale, Intl_Date_Formatter::NONE, Intl_Date_Formatter::SHORT);
            $time_short = $format->get_pattern();
            $strftime2intl = ['%a' => 'E', '%A' => 'EEEE', '%b' => 'MMM', '%B' => 'MMMM', '%d' => 'dd', '%H' => 'HH', '%k' => 'H', '%m' => 'MM', '%M' => 'mm', '%S' => 'ss', '%T' => 'HH:mm:ss', '%x' => $date_short, '%X' => $time_short, '%y' => 'yy', '%Y' => 'y', '%z' => 'ZZZZ', '%Z' => 'zzzz'];
            $this->strftime2intl = ['from' => array_keys($strftime2intl), 'to' => array_values($strftime2intl)];
        }
    }
    // -----
    // A couple of public functions to control whether or not the class' debug
    // processing is to be enabled or disabled.
    //
    /**
     * @since ZC v1.5.8
     */
    public function enable_debug(): void
    {
        $this->debug = true;
        $this->debug('Debug enabled: ' . PHP_EOL . var_export($this, true));
    }
    /**
     * @since ZC v1.5.8
     */
    public function disable_debug(): void
    {
        $this->debug = false;
    }
    /**
     * @param string $format  output method should start with a strftime-format string
     * @param string|null $calendar_locale Optional calendar-related locale. eg: 'ja_JP@calendar=japanese'
     *
     * @return false|string
     * @since ZC v1.5.8
     */
    public function output(string $format, int $timestamp = 0, ?string $calendar_locale = null)
    {
        if ($timestamp === 0) {
            $timestamp = time();
        }
        // -----
        // If the to-be-used function is strftime, format the requested string.
        //
        if ($this->use_strftime === true) {
            $converted_format = $format;
            $output = strftime($format, $timestamp);
            // -----
            // Otherwise, if there's no international date support, format the requested string using date.
            //
        } elseif ($this->use_intl_date === false) {
            $converted_format = $this->convert_format($format, $this->strftime2date);
            $output = date($converted_format, $timestamp);
            // -----
            // Otherwise, the string is to be formatted using the IntlDateFormatter ...
            //
        } else {
            // -----
            // If the locale has changes (as it might between the class construction and
            // this method, re-initialize the conversion arrays for the current locale.
            //
            if ($this->locale !== setlocale(LC_TIME, '0')) {
                $this->initialize_conversion_arrays();
            }
            $calendar = Intl_Date_Formatter::GREGORIAN;
            if (!empty($calendar_locale)) {
                $calendar = Intl_Calendar::create_instance(null, $calendar_locale);
            }
            $converted_format = $this->convert_format($format, $this->strftime2intl);
            $this->date_object = datefmt_create($this->locale, Intl_Date_Formatter::FULL, Intl_Date_Formatter::FULL, date_default_timezone_get(), $calendar, $converted_format);
            $output = $this->date_object->format($timestamp);
            if ($output === false) {
                trigger_error(sprintf("Formatting error using '%s': %s (%d)", $converted_format, $this->date_object->get_error_message(), $this->date_object->get_error_code()), E_USER_WARNING);
            }
        }
        $additional_message = $format === $converted_format ? '' : ", with format converted to '{$converted_format}'";
        $this->debug("zcDate output for '{$format}' with timestamp ({$timestamp})" . $additional_message . ": '" . json_encode($output) . "'");
        return $output;
    }
    /**
     * @since ZC v1.5.8
     */
    protected function convert_format(string $format, array $replacements): string
    {
        return str_replace($replacements['from'], $replacements['to'], $format);
    }
    /**
     * @param string $date  The date to be validated, according to the same rules as strtotime.
     *
     * @return bool  Indicates whether/not the supplied date is valid
     * @since ZC v2.0.0
     */
    public static function validate_date(string $date): bool
    {
        ['year' => $year, 'month' => $month, 'day' => $day, 'warning_count' => $warning_count, 'error_count' => $error_count] = date_parse($date);
        return $year !== false && $month !== false && $day !== false && $warning_count + $error_count === 0;
    }
    /**
     * @since ZC v1.5.8
     */
    protected function debug(string $message)
    {
        if ($this->debug === true) {
            error_log($message . PHP_EOL);
        }
    }
}