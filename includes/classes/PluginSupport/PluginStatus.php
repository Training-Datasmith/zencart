<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: lat9 2025 Sep 27 New in v2.2.0 $
 *
 * @since ZC v2.2.0
 */
namespace Zencart\Plugin_Support;

class Plugin_Status
{
    public const NOT_INSTALLED = 0;
    public const ENABLED = 1;
    public const DISABLED = 2;
}