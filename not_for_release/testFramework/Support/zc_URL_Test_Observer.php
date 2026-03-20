<?php

declare(strict_types=1);
/**
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\Support;

use base;

/**
 * Class zcURLTestObserver
 */
class zcURLTestObserver extends base
{
    public static $CHANGE_NOTHING = 0;
    public static $CHANGE_CONNECTION = 1;
    public static $CHANGE_PAGE = 2;
    public static $CHANGE_PARAMETERS = 4;
    public static $CHANGE_STATIC = 8;

    /**
     * @var int
     */
    public $mode;

    public function __construct()
    {
        $this->attach($this, ['NOTIFY_HANDLE_HREF_LINK']);
        $this->mode = 0;
    }

    public function update(&$class, $eventID, $paramsArray, &$page, &$parameters, &$connection, &$static): void
    {
        if ($this->mode & zcURLTestObserver::$CHANGE_CONNECTION) {
            if ($connection == 'SSL') {
                $connection = 'NONSSL';
            } else {
                $connection = 'SSL';
            }
        }

        if ($this->mode & zcURLTestObserver::$CHANGE_PAGE) {
            $page = 'dummy_page';
        }

        if ($this->mode & zcURLTestObserver::$CHANGE_PARAMETERS) {
            $parameters = ['changed' => 'parameters'];
        }

        if ($this->mode & zcURLTestObserver::$CHANGE_STATIC) {
            $static = !$static;
        }
    }
}
