<?php

declare (strict_types=1);
/**
 * PHPMailer - PHP email creation and transport class.
 * PHP Version 5.5.
 *
 * @see https://github.com/PHPMailer/PHPMailer/ The PHPMailer GitHub project
 *
 * @author    Marcus Bointon (Synchro/coolbru) <phpmailer@synchromedia.co.uk>
 * @author    Jim Jagielski (jimjag) <jimjag@gmail.com>
 * @author    Andy Prevost (codeworxtech) <codeworxtech@users.sourceforge.net>
 * @author    Brent R. Matzelle (original founder)
 * @copyright 2012 - 2023 Marcus Bointon
 * @copyright 2010 - 2012 Jim Jagielski
 * @copyright 2004 - 2009 Andy Prevost
 * @license   https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU Lesser General Public License
 * @note      This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */
namespace Php_Mailer\Php_Mailer;

/**
 * Configure PHPMailer with DSN string.
 *
 * @see https://en.wikipedia.org/wiki/Data_source_name
 *
 * @author Oleg Voronkovich <oleg-voronkovich@yandex.ru>
 */
class Dsn_Configurator
{
    /**
     * Create new PHPMailer instance configured by DSN.
     *
     * @param string $dsn        DSN
     * @param bool   $exceptions Should we throw external exceptions?
     *
     * @return PHPMailer
     */
    public static function mailer($dsn, $exceptions = null)
    {
        static $configurator = null;
        if (null === $configurator) {
            $configurator = new Dsn_Configurator();
        }
        return $configurator->configure(new Php_Mailer($exceptions), $dsn);
    }
    /**
     * Configure PHPMailer instance with DSN string.
     *
     * @param PHPMailer $mailer PHPMailer instance
     * @param string    $dsn    DSN
     */
    public function configure(Php_Mailer $mailer, $dsn): Php_Mailer
    {
        $config = $this->parse_dsn($dsn);
        $this->apply_config($mailer, $config);
        return $mailer;
    }
    /**
     * Parse DSN string.
     *
     * @param string $dsn DSN
     *
     * @throws Exception If DSN is malformed
     *
     * @return array Configuration
     */
    private function parse_dsn($dsn)
    {
        $config = $this->parse_url($dsn);
        if (false === $config || !isset($config['scheme']) || !isset($config['host'])) {
            throw new Exception('Malformed DSN');
        }
        if (isset($config['query'])) {
            parse_str($config['query'], $config['query']);
        }
        return $config;
    }
    /**
     * Apply configuration to mailer.
     *
     * @param PHPMailer $mailer PHPMailer instance
     * @param array     $config Configuration
     *
     * @throws Exception If scheme is invalid
     */
    private function apply_config(Php_Mailer $mailer, array $config): void
    {
        switch ($config['scheme']) {
            case 'mail':
                $mailer->is_mail();
                break;
            case 'sendmail':
                $mailer->is_sendmail();
                break;
            case 'qmail':
                $mailer->is_qmail();
                break;
            case 'smtp':
            case 'smtps':
                $mailer->is_smtp();
                $this->configure_smtp($mailer, $config);
                break;
            default:
                throw new Exception(sprintf('Invalid scheme: "%s". Allowed values: "mail", "sendmail", "qmail", "smtp", "smtps".', $config['scheme']));
        }
        if (isset($config['query'])) {
            $this->configure_options($mailer, $config['query']);
        }
    }
    /**
     * Configure SMTP.
     *
     * @param PHPMailer $mailer PHPMailer instance
     * @param array     $config Configuration
     */
    private function configure_smtp(\Php_Mailer\Php_Mailer\Php_Mailer $mailer, array $config): void
    {
        $is_smtps = 'smtps' === $config['scheme'];
        if ($is_smtps) {
            $mailer->smtp_secure = Php_Mailer::ENCRYPTION_STARTTLS;
        }
        $mailer->Host = $config['host'];
        if (isset($config['port'])) {
            $mailer->Port = $config['port'];
        } elseif ($is_smtps) {
            $mailer->Port = SMTP::DEFAULT_SECURE_PORT;
        }
        $mailer->smtp_auth = isset($config['user']) || isset($config['pass']);
        if (isset($config['user'])) {
            $mailer->Username = $config['user'];
        }
        if (isset($config['pass'])) {
            $mailer->Password = $config['pass'];
        }
    }
    /**
     * Configure options.
     *
     * @param PHPMailer $mailer  PHPMailer instance
     * @param array     $options Options
     *
     * @throws Exception If option is unknown
     */
    private function configure_options(Php_Mailer $mailer, $options): void
    {
        $allowed_options = get_object_vars($mailer);
        unset($allowed_options['Mailer']);
        unset($allowed_options['SMTPAuth']);
        unset($allowed_options['Username']);
        unset($allowed_options['Password']);
        unset($allowed_options['Hostname']);
        unset($allowed_options['Port']);
        unset($allowed_options['ErrorInfo']);
        $allowed_options = \array_keys($allowed_options);
        foreach ($options as $key => $value) {
            if (!in_array($key, $allowed_options)) {
                throw new Exception(sprintf('Unknown option: "%s". Allowed values: "%s"', $key, implode('", "', $allowed_options)));
            }
            $mailer->{$key} = match ($key) {
                'AllowEmpty', 'SMTPAutoTLS', 'SMTPKeepAlive', 'SingleTo', 'UseSendmailOptions', 'do_verp', 'DKIM_copyHeaderFields' => (bool) $value,
                'Priority', 'SMTPDebug', 'WordWrap' => (int) $value,
                default => $value,
            };
        }
    }
    /**
     * Parse a URL.
     * Wrapper for the built-in parse_url function to work around a bug in PHP 5.5.
     *
     * @param string $url URL
     *
     * @return array|false
     */
    protected function parse_url($url)
    {
        if (\PHP_VERSION_ID >= 50600 || !str_contains($url, '?')) {
            return parse_url($url);
        }
        $chunks = explode('?', $url);
        if (is_array($chunks)) {
            $result = parse_url($chunks[0]);
            if (is_array($result)) {
                $result['query'] = $chunks[1];
            }
            return $result;
        }
        return false;
    }
}