<?php

declare(strict_types=1);

namespace Tests\Support;

use base;

/**
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */
class zcNotifierTestObserver extends base
{
    public function __construct()
    {
        $this->attach($this, ['NOTIFY_TEST_SNAKE_CASE']);
        $this->attach($this, ['NOTIFY_TEST_CAMEL_CASE']);
        $this->attach($this, ['NOTIFY_TEST_UPDATE']);
    }

    public function notify_test_snake_case(&$class, $eventID, $param1, &$param2): void
    {
        $param2 = 'snake';
    }

    public function updateNotifyTestCamelCase(&$class, $eventID, $param1, &$param2): void
    {
        $param2 = 'camel';
    }

    public function update(&$class, $eventID, $param1, &$param2): void
    {
        $param2 = 'update';
    }
}
