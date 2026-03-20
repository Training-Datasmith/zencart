<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 * @since ZC v2.0.0
 */
class Zc_Ajax_Admin_Session_Change extends base
{
    protected $supported_names = ['imageView'];
    /**
     * @since ZC v2.0.0
     */
    public function change(): string
    {
        // -----
        // Deny access unless running under the admin.
        //
        return '';
    }
}