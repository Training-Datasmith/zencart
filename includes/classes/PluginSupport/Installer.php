<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
namespace Zencart\Plugin_Support;

/**
 * @since ZC v1.5.7
 */
class Installer
{
    protected string $plugin_dir;
    protected string $plugin_key;
    protected string $version;
    protected ?string $old_version = null;
    public function __construct(protected Sql_Patch_Installer $patch_installer, protected Scripted_Installer_Factory $scripted_installer_factory, protected Plugin_Error_Container $error_container)
    {
    }
    /**
     * @since ZC v2.1.0
     */
    public function set_versions(string $plugin_dir, string $plugin_key, string $version, ?string $old_version = null): void
    {
        $this->plugin_dir = $plugin_dir;
        $this->plugin_key = $plugin_key;
        $this->version = $version;
        $this->old_version = $old_version;
    }
    /**
     * @since ZC v2.1.0
     */
    public function get_version_information(): array
    {
        return ['pluginKey' => $this->plugin_key, 'pluginDir' => $this->plugin_dir, 'version' => $this->version, 'oldVersion' => $this->old_version];
    }
    /**
     * @since ZC v1.5.7
     */
    public function execute_installers($plugin_dir): void
    {
        $this->execute_patch_installer($plugin_dir);
        if ($this->error_container->has_errors()) {
            return;
        }
        $this->execute_scripted_installer($plugin_dir);
    }
    /**
     * @since ZC v1.5.7
     */
    public function execute_uninstallers($plugin_dir): void
    {
        $this->execute_patch_uninstaller($plugin_dir);
        if ($this->error_container->has_errors()) {
            return;
        }
        $this->execute_scripted_uninstaller($plugin_dir);
    }
    /**
     * @since ZC v1.5.8
     */
    public function execute_upgraders(string $plugin_dir, $old_version): void
    {
        $this->execute_scripted_upgrader($plugin_dir, $old_version);
    }
    /**
     * @since ZC v1.5.7
     */
    protected function execute_patch_installer(string $plugin_dir): void
    {
        $patch_file = 'install.sql';
        $this->execute_patch_file($plugin_dir, $patch_file);
    }
    /**
     * @since ZC v1.5.7
     */
    protected function execute_patch_uninstaller(string $plugin_dir): void
    {
        $patch_file = 'uninstall.sql';
        $this->execute_patch_file($plugin_dir, $patch_file);
    }
    /**
     * @since ZC v1.5.7
     */
    protected function execute_patch_file(string $plugin_dir, string $patch_file): void
    {
        if (!file_exists($plugin_dir . '/Installer/' . $patch_file)) {
            return;
        }
        $lines = file($plugin_dir . '/Installer/' . $patch_file);
        $param_lines = $this->patch_installer->parse($lines);
        if ($this->error_container->has_errors()) {
            return;
        }
        $this->patch_installer->execute_patch_sql($param_lines);
    }
    /**
     * @since ZC v1.5.7
     */
    protected function execute_scripted_installer(string $plugin_dir): void
    {
        if (!file_exists($plugin_dir . '/Installer/ScriptedInstaller.php')) {
            return;
        }
        $scripted_installer = $this->scripted_installer_factory->make($plugin_dir);
        $scripted_installer->set_version_details($this->get_version_information());
        $scripted_installer->do_install();
    }
    /**
     * @since ZC v1.5.7
     */
    protected function execute_scripted_uninstaller(string $plugin_dir): void
    {
        if (!file_exists($plugin_dir . '/Installer/ScriptedInstaller.php')) {
            return;
        }
        $scripted_installer = $this->scripted_installer_factory->make($plugin_dir);
        $scripted_installer->set_version_details($this->get_version_information());
        $scripted_installer->do_uninstall();
    }
    /**
     * @since ZC v1.5.8
     */
    protected function execute_scripted_upgrader(string $plugin_dir, $old_version): void
    {
        if (!file_exists($plugin_dir . '/Installer/ScriptedInstaller.php')) {
            return;
        }
        $scripted_installer = $this->scripted_installer_factory->make($plugin_dir);
        $scripted_installer->set_version_details($this->get_version_information());
        $scripted_installer->do_upgrade($old_version);
    }
    /**
     * @since ZC v1.5.8a
     */
    public function get_error_container(): Plugin_Error_Container
    {
        return $this->error_container;
    }
}