<?php

declare(strict_types=1);

namespace Tests\Support;

use Zencart\Traits\NotifierManager;

class zcNotifierTraitAliasTestObject
{
    use NotifierManager;

    public string $foo;

    public function fireNotifierValid(): string
    {
        $this->foo = 'valid';
        $this->notify('NOTIFY_ORDER_CART_SUBTOTAL_CALCULATE');
        return $this->foo;
    }

    public function fireNotifierInvalid(): string
    {
        $this->foo = 'invalid';
        $this->notify('NOTIFIYFOO_ORDER_CART_SUBTOTAL_CALCULATE');
        return $this->foo;
    }
}
