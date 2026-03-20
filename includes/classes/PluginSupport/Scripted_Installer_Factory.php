<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
namespace Zencart\Plugin_Support;

use Query_Factory;
/**
 * @since ZC v1.5.7
 */
class Scripted_Installer_Factory
{
    public function __construct(protected Query_Factory $db_conn, protected Plugin_Error_Container $error_container)
    {
    }
    /**
     * @since ZC v1.5.7
     */
    public function make(string $plugin_dir): Scripted_Installer
    {
        require_once $plugin_dir . '/Installer/ScriptedInstaller.php';
        return new \Scripted_Installer($this->db_conn, $this->error_container);
    }
}