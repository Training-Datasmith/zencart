<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 29 Modified in v2.2.0 $
 */
namespace Zencart\Plugin_Support;

use Query_Factory;
/**
 * @since ZC v1.5.7
 */
class Base_Plugin_Installer
{
    /**
     * $pluginDir is the directory where the plugin is located
     */
    protected string $plugin_dir;
    public function __construct(protected Query_Factory $db_conn, protected Installer $plugin_installer, protected Plugin_Error_Container $error_container)
    {
    }
    /**
     * @since ZC v1.5.7
     */
    public function process_install(string $plugin_key, string $version): bool
    {
        $this->plugin_dir = DIR_FS_CATALOG . 'zc_plugins/' . $plugin_key . '/' . $version;
        $this->load_installer_language_file('main.php');
        $this->plugin_installer->set_versions($this->plugin_dir, $plugin_key, $version);
        $this->plugin_installer->execute_installers($this->plugin_dir);
        if ($this->error_container->has_errors()) {
            return false;
        }
        $this->set_plugin_version_status($plugin_key, $version, Plugin_Status::ENABLED);
        return true;
    }
    /**
     * @since ZC v1.5.7
     */
    public function process_uninstall(string $plugin_key, string $version): bool
    {
        $this->plugin_dir = DIR_FS_CATALOG . 'zc_plugins/' . $plugin_key . '/' . $version;
        $this->load_installer_language_file('main.php');
        $this->set_plugin_version_status($plugin_key, '', Plugin_Status::NOT_INSTALLED);
        $this->plugin_installer->set_versions($this->plugin_dir, $plugin_key, $version);
        $this->plugin_installer->execute_uninstallers($this->plugin_dir);
        if ($this->error_container->has_errors()) {
            return false;
        }
        return true;
    }
    /**
     * @since ZC v1.5.8
     */
    public function process_upgrade(string $plugin_key, string $version, $old_version): bool
    {
        $this->plugin_dir = DIR_FS_CATALOG . 'zc_plugins/' . $plugin_key . '/' . $version;
        $this->load_installer_language_file('main.php');
        $this->plugin_installer->set_versions($this->plugin_dir, $plugin_key, $version, $old_version);
        $this->plugin_installer->execute_upgraders($this->plugin_dir, $old_version);
        if ($this->error_container->has_errors()) {
            return false;
        }
        $this->set_plugin_version_status($plugin_key, $old_version, Plugin_Status::NOT_INSTALLED);
        $this->set_plugin_version_status($plugin_key, $version, Plugin_Status::ENABLED);
        return true;
    }
    /**
     * @since ZC v1.5.7
     */
    public function process_disable($plugin_key, $version): void
    {
        $this->set_plugin_version_status($plugin_key, $version, Plugin_Status::DISABLED);
    }
    /**
     * @since ZC v1.5.7
     */
    public function process_enable($plugin_key, $version): void
    {
        $this->set_plugin_version_status($plugin_key, $version, Plugin_Status::ENABLED);
    }
    /**
     * @since ZC v1.5.7
     */
    protected function set_plugin_version_status($plugin_key, $version, $status): void
    {
        $sql = 'UPDATE ' . TABLE_PLUGIN_CONTROL . ' SET status = :status:, version = :version: WHERE unique_key = :uniqueKey:';
        $sql = $this->db_conn->bind_vars($sql, ':status:', $status, 'integer');
        $sql = $this->db_conn->bind_vars($sql, ':uniqueKey:', $plugin_key, 'string');
        $sql = $this->db_conn->bind_vars($sql, ':version:', $version, 'string');
        $this->db_conn->execute($sql);
    }
    /**
     * Loads the "main.php" language file. This handles "defines" for language-strings. It does NOT handle language-arrays.
     * @since ZC v1.5.7
     */
    protected function load_installer_language_file(string $file): void
    {
        $lng = $_SESSION['language'];
        $filename = $this->plugin_dir . '/Installer/languages/' . $lng . '/' . $file;
        if (file_exists($filename)) {
            require_once $filename;
            return;
        }
        if ($lng === 'english') {
            return;
        }
        $filename = $this->plugin_dir . '/Installer/languages/english/' . $file;
        if (file_exists($filename)) {
            require_once $filename;
        }
    }
    /**
     * @since ZC v1.5.8a
     */
    public function get_error_container(): Plugin_Error_Container
    {
        return $this->error_container;
    }
}