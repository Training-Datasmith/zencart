<?php

declare(strict_types=1);

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

//require_once dirname(__FILE__).'/sfYaml.php';

/**
 * sfYamlInline implements a YAML parser/dumper for the YAML inline syntax.
 *
 * @subpackage yaml
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version $Id: DrByte 2025 Oct 03 Modified in v2.2.0 $
 * @codeCoverageIgnore
 */
class sfYamlInline
{
    public const REGEX_QUOTED_STRING = '(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\']*(?:\'\'[^\']*)*)\')';

    /**
     * Convert a YAML string to a PHP array.
     *
     * @param string $value A YAML string
     *
     * @return array A PHP array representing the YAML string
     */
    public static function load($value)
    {
        $value = trim($value);

        if (0 == strlen($value)) {
            return '';
        }

        if (function_exists('mb_internal_encoding') && ((int) ini_get('mbstring.func_overload')) & 2) {
            $mbEncoding = mb_internal_encoding();
            mb_internal_encoding('ASCII');
        }

        $result = match ($value[0]) {
            '[' => self::parseSequence($value),
            '{' => self::parseMapping($value),
            default => self::parseScalar($value),
        };

        if (isset($mbEncoding)) {
            mb_internal_encoding($mbEncoding);
        }

        return $result;
    }

    /**
     * Dumps a given PHP variable to a YAML string.
     *
     * @param mixed $value The PHP variable to convert
     *
     * @return string The YAML string representing the PHP array
     */
    public static function dump($value)
    {
        if ('1.1' === sfYaml::getSpecVersion()) {
            $trueValues = ['true', 'on', '+', 'yes', 'y'];
            $falseValues = ['false', 'off', '-', 'no', 'n'];
        } else {
            $trueValues = ['true'];
            $falseValues = ['false'];
        }

        switch (true) {
            case is_resource($value):
                throw new InvalidArgumentException('Unable to dump PHP resources in a YAML file.');
            case is_object($value):
                return '!!php/object:'.serialize($value);
            case is_array($value):
                return self::dumpArray($value);
            case null === $value:
                return 'null';
            case true === $value:
                return 'true';
            case false === $value:
                return 'false';
            case ctype_digit((string) $value):
                return is_string($value) ? "'$value'" : $value;
            case is_numeric($value):
                return is_infinite($value) ? str_ireplace('INF', '.Inf', strval($value)) : (is_string($value) ? "'$value'" : $value);
            case str_contains((string) $value, "\n") || str_contains((string) $value, "\r"):
                return sprintf('"%s"', str_replace(['"', "\n", "\r"], ['\\"', '\n', '\r'], $value));
            case preg_match('/[ \s \' " \: \{ \} \[ \] , & \* \# \?] | \A[ - ? | < > = ! % @ ` ]/x', (string) $value):
                return sprintf("'%s'", str_replace('\'', '\'\'', $value));
            case '' == $value:
                return "''";
            case preg_match(self::getTimestampRegex(), (string) $value):
            case in_array(strtolower((string) $value), $trueValues):
            case in_array(strtolower((string) $value), $falseValues):
            case in_array(strtolower((string) $value), ['null', '~']):
                return "'$value'";
            default:
                return $value;
        }
    }

    /**
     * Dumps a PHP array to a YAML string.
     *
     * @param array $value The PHP array to dump
     *
     * @return string The YAML string representing the PHP array
     */
    protected static function dumpArray($value): string
    {
        // array
        $keys = array_keys($value);
        if (
            (1 == count($keys) && '0' == $keys[0])
            ||
            (count($keys) > 1 && array_reduce($keys, fn (int $v, $w): int => $v + $w, 0) == count($keys) * (count($keys) - 1) / 2)) {
            $output = [];
            foreach ($value as $val) {
                $output[] = self::dump($val);
            }

            return sprintf('[%s]', implode(', ', $output));
        }

        // mapping
        $output = [];
        foreach ($value as $key => $val) {
            $output[] = sprintf('%s: %s', self::dump($key), self::dump($val));
        }

        return sprintf('{ %s }', implode(', ', $output));
    }

    /**
     * Parses a scalar to a YAML string.
     *
     * @param scalar  $scalar
     * @param string  $delimiters
     * @param array   $stringDelimiter
     * @param integer $i
     * @param boolean $evaluate
     *
     * @return string A YAML string
     */
    public static function parseScalar($scalar, $delimiters = null, $stringDelimiters = ['"', "'"], &$i = 0, $evaluate = true)
    {
        if (in_array($scalar[$i], $stringDelimiters)) {
            // quoted scalar
            return self::parseQuotedScalar($scalar, $i);
        }
        // "normal" string
        if (!$delimiters) {
            $output = substr($scalar, $i);
            $i += strlen($output);

            // remove comments
            if (false !== $strpos = strpos($output, ' #')) {
                $output = rtrim(substr($output, 0, $strpos));
            }
        } elseif (preg_match('/^(.+?)('.implode('|', $delimiters).')/', substr($scalar, $i), $match)) {
            $output = $match[1];
            $i += strlen($output);
        } else {
            throw new InvalidArgumentException(sprintf('Malformed inline YAML string (%s).', $scalar));
        }

        return $evaluate ? self::evaluateScalar($output) : $output;
    }

    /**
     * Parses a quoted scalar to YAML.
     *
     * @param string  $scalar
     * @param integer $i
     *
     * @return string A YAML string
     */
    protected static function parseQuotedScalar($scalar, &$i): string|array
    {
        if (!preg_match('/'.self::REGEX_QUOTED_STRING.'/Au', substr($scalar, $i), $match)) {
            throw new InvalidArgumentException(sprintf('Malformed inline YAML string (%s).', substr($scalar, $i)));
        }

        $output = substr($match[0], 1, strlen($match[0]) - 2);

        if ('"' == $scalar[$i]) {
            // evaluate the string
            $output = str_replace(['\\"', '\\n', '\\r'], ['"', "\n", "\r"], $output);
        } else {
            // unescape '
            $output = str_replace('\'\'', '\'', $output);
        }

        $i += strlen($match[0]);

        return $output;
    }

    /**
     * Parses a sequence to a YAML string.
     *
     * @param integer $i
     * @return string A YAML string
     */
    protected static function parseSequence(string $sequence, &$i = 0): array
    {
        $output = [];
        $len = strlen($sequence);
        $i += 1;

        // [foo, bar, ...]
        while ($i < $len) {
            switch ($sequence[$i]) {
                case '[':
                    // nested sequence
                    $output[] = self::parseSequence($sequence, $i);
                    break;
                case '{':
                    // nested mapping
                    $output[] = self::parseMapping($sequence, $i);
                    break;
                case ']':
                    return $output;
                case ',':
                case ' ':
                    break;
                default:
                    $isQuoted = in_array($sequence[$i], ['"', "'"]);
                    $value = self::parseScalar($sequence, [',', ']'], ['"', "'"], $i);

                    if (!$isQuoted && str_contains($value, ': ')) {
                        // embedded mapping?
                        try {
                            $value = self::parseMapping('{'.$value.'}');
                        } catch (InvalidArgumentException) {
                            // no, it's not
                        }
                    }

                    $output[] = $value;

                    --$i;
            }

            ++$i;
        }

        throw new InvalidArgumentException(sprintf('Malformed inline YAML string %s', $sequence));
    }

    /**
     * Parses a mapping to a YAML string.
     *
     * @param integer $i
     * @return string A YAML string
     */
    protected static function parseMapping(string $mapping, &$i = 0): array
    {
        $output = [];
        $len = strlen($mapping);
        $i += 1;

        // {foo: bar, bar:foo, ...}
        while ($i < $len) {
            switch ($mapping[$i]) {
                case ' ':
                case ',':
                    ++$i;
                    continue 2;
                case '}':
                    return $output;
            }

            // key
            $key = self::parseScalar($mapping, [':', ' '], ['"', "'"], $i, false);

            // value
            $done = false;
            while ($i < $len) {
                switch ($mapping[$i]) {
                    case '[':
                        // nested sequence
                        $output[$key] = self::parseSequence($mapping, $i);
                        $done = true;
                        break;
                    case '{':
                        // nested mapping
                        $output[$key] = self::parseMapping($mapping, $i);
                        $done = true;
                        break;
                    case ':':
                    case ' ':
                        break;
                    default:
                        $output[$key] = self::parseScalar($mapping, [',', '}'], ['"', "'"], $i);
                        $done = true;
                        --$i;
                }

                ++$i;

                if ($done) {
                    continue 2;
                }
            }
        }

        throw new InvalidArgumentException(sprintf('Malformed inline YAML string %s', $mapping));
    }

    /**
     * Evaluates scalars and replaces magic values.
     *
     * @param string $scalar
     *
     * @return string A YAML string
     */
    protected static function evaluateScalar($scalar)
    {
        $scalar = trim($scalar);

        if ('1.1' === sfYaml::getSpecVersion()) {
            $trueValues = ['true', 'on', '+', 'yes', 'y'];
            $falseValues = ['false', 'off', '-', 'no', 'n'];
        } else {
            $trueValues = ['true'];
            $falseValues = ['false'];
        }

        switch (true) {
            case 'null' == strtolower($scalar):
            case '' == $scalar:
            case '~' == $scalar:
                return null;
            case str_starts_with($scalar, '!str'):
                return substr($scalar, 5);
            case str_starts_with($scalar, '! '):
                return intval(self::parseScalar(substr($scalar, 2)));
            case str_starts_with($scalar, '!!php/object:'):
                return unserialize(substr($scalar, 13));
            case ctype_digit($scalar):
                $raw = $scalar;
                $cast = intval($scalar);
                return '0' == $scalar[0] ? octdec($scalar) : (($raw == (string) $cast) ? $cast : $raw);
            case in_array(strtolower($scalar), $trueValues):
                return true;
            case in_array(strtolower($scalar), $falseValues):
                return false;
            case is_numeric($scalar):
                return '0x' == $scalar[0].$scalar[1] ? hexdec($scalar) : floatval($scalar);
            case 0 == strcasecmp($scalar, '.inf'):
            case 0 == strcasecmp($scalar, '.NaN'):
                return -log(0);
            case 0 == strcasecmp($scalar, '-.inf'):
                return log(0);
            case preg_match('/^(-|\+)?[0-9,]+(\.[0-9]+)?$/', $scalar):
                return floatval(str_replace(',', '', $scalar));
            case preg_match(self::getTimestampRegex(), $scalar):
                return strtotime($scalar);
            default:
                return $scalar;
        }
    }

    protected static function getTimestampRegex(): string
    {
        return <<<EOF
    ~^
    (?P<year>[0-9][0-9][0-9][0-9])
    -(?P<month>[0-9][0-9]?)
    -(?P<day>[0-9][0-9]?)
    (?:(?:[Tt]|[ \t]+)
    (?P<hour>[0-9][0-9]?)
    :(?P<minute>[0-9][0-9])
    :(?P<second>[0-9][0-9])
    (?:\.(?P<fraction>[0-9]*))?
    (?:[ \t]*(?P<tz>Z|(?P<tz_sign>[-+])(?P<tz_hour>[0-9][0-9]?)
    (?::(?P<tz_minute>[0-9][0-9]))?))?)?
    $~x
EOF;
    }
}
