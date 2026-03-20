<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
namespace Zencart\Plugin_Support;

/**
 * @since ZC v1.5.7
 */
class Plugin_Error_Container
{
    /**
     * $logErrors is an array of error messages
     */
    protected array $log_errors = [];
    /**
     * $friendlyErrors is a subset of $logErrors that have a friendly message (a known error with additional information)
     */
    protected array $friendly_errors = [];
    /**
     * @param object $logger
     */
    public function __construct(
        /**
         * $logger "null" the logger to use.
         */
        protected $logger = null
    )
    {
        $this->log_errors = [];
        $this->friendly_errors = [];
    }
    /**
     * @since ZC v1.5.7
     */
    public function has_log_errors(): int
    {
        return count($this->log_errors);
    }
    /**
     * @since ZC v1.5.7
     */
    public function has_friendly_errors(): int
    {
        return count($this->friendly_errors);
    }
    /**
     * @since ZC v1.5.7
     */
    public function add_error($log_severity, $log_message, $use_log_message_for_friendly = false, $friendly_message = ''): void
    {
        if ($use_log_message_for_friendly) {
            $friendly_message = $log_message;
        }
        $this->log_errors[] = $log_message;
        if ($friendly_message === '') {
            return;
        }
        $friendly_hash = hash('md5', (string) $friendly_message);
        $this->friendly_errors[$friendly_hash] = $friendly_message;
        if ($this->logger) {
            // do something here for external logging;
        }
    }
    /**
     * @since ZC v1.5.7
     */
    public function has_errors(): int
    {
        return count($this->log_errors + $this->friendly_errors);
    }
    /**
     * @since ZC v1.5.7
     */
    public function get_friendly_errors(): array
    {
        return $this->friendly_errors;
    }
    /**
     * @since ZC v1.5.7
     */
    public function get_log_errors(): array
    {
        return $this->log_errors;
    }
}