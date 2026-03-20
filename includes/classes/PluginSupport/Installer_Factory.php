<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
namespace Zencart\Plugin_Support;

use Query_Factory;
use Zencart\Exceptions\Plugin_Installer_Exception;
/**
 * @since ZC v1.5.7
 */
class Installer_Factory
{
    public function __construct(protected Query_Factory $db_conn, protected Installer $plugin_installer, protected Plugin_Error_Container $error_container)
    {
    }
    /**
     * @since ZC v1.5.7
     */
    public function make(string $plugin, string $version): \Zencart\Plugin_Support\Base_Plugin_Installer|\Installer
    {
        $plugin_dir = DIR_FS_CATALOG . 'zc_plugins/' . $plugin . '/';
        $version_dir = $plugin_dir . $version . '/';
        if (!is_dir($plugin_dir)) {
            throw new Plugin_Installer_Exception('NO PLUGIN DIRECTORY');
        }
        if (!is_dir($version_dir)) {
            throw new Plugin_Installer_Exception('NO PLUGIN VERSION DIRECTORY');
        }
        if (!file_exists($version_dir . 'manifest.php')) {
            throw new Plugin_Installer_Exception('NO VERSION MANIFEST');
        }
        if (!file_exists($version_dir . 'Installer/Installer.php')) {
            return new Base_Plugin_Installer($this->db_conn, $this->plugin_installer, $this->error_container);
        }
        require_once $version_dir . 'Installer/Installer.php';
        return new \Installer($this->db_conn, $this->plugin_installer, $this->error_container);
    }
}