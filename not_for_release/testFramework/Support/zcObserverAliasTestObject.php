<?php

declare(strict_types=1);

namespace Tests\Support;

use base;

class zcObserverAliasTestObject extends base
{
    public function __construct()
    {
        $this->attach($this, ['NOTIFY_DOWNLOAD_READY_TO_STREAM']);
        $this->attach($this, ['NOTIFIY_ORDER_CART_SUBTOTAL_CALCULATE']);
    }

    public function updateNotifiyOrderCartSubtotalCalculate(&$class, $eventID, $paramsArray = []): void
    {
        $class->foo = $eventID;
    }
}
