<?php

declare (strict_types=1);
namespace Detection\Cache;

use Psr\Simple_Cache\InvalidArgumentException;
class Cache_Invalid_Argument_Exception extends Cache_Exception implements InvalidArgumentException
{
}