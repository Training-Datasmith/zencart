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
class Scripted_Installer
{
    use Scripted_Install_Helpers;
    // Extended classes can access these variables to understand what version/etc they are operating on.
    protected string $plugin_dir;
    protected string $plugin_key;
    protected string $version;
    protected ?string $old_version = null;
    // null if not in upgrade mode
    public function __construct(protected Query_Factory $db_conn, protected Plugin_Error_Container $error_container)
    {
    }
    /***** THESE ARE THE 3 METHODS FOR IMPLEMENTATION IN EXTENDED CLASSES *********/
    /***** There is no need to implement any other methods in extended classes ****/
    /**
     * @since ZC v1.5.7
     */
    protected function execute_install(): bool
    {
        return true;
    }
    /**
     * @since ZC v1.5.7
     */
    protected function execute_uninstall(): bool
    {
        return true;
    }
    /**
     * @since ZC v1.5.8
     */
    protected function execute_upgrade($old_version): bool
    {
        return true;
    }
    /******** Internal methods ***********/
    /**
     * @since ZC v2.1.0
     */
    public function set_version_details(array $version_details): void
    {
        $this->plugin_key = $version_details['pluginKey'];
        $this->plugin_dir = $version_details['pluginDir'];
        $this->version = $version_details['version'];
        $this->old_version = $version_details['oldVersion'];
    }
    /**
     * @since ZC v1.5.7
     */
    public function do_install(): ?bool
    {
        return $this->execute_install();
    }
    /**
     * @since ZC v1.5.7
     */
    public function do_uninstall(): ?bool
    {
        $uninstalled = $this->execute_uninstall();
        $this->uninstall_zen_core_db_fields();
        return $uninstalled;
    }
    /**
     * @since ZC v1.5.8
     */
    public function do_upgrade($old_version): ?bool
    {
        $upgraded = $this->execute_upgrade($old_version);
        $this->update_zen_core_db_fields($old_version);
        return $upgraded;
    }
    /**
     * @since ZC v1.5.7
     */
    protected function execute_installer_sql(string $sql): bool
    {
        $this->db_conn->die_on_errors = false;
        $this->db_conn->Execute($sql);
        if ($this->db_conn->error_number !== 0) {
            $this->error_container->add_error(0, $this->db_conn->error_text, true, PLUGIN_INSTALL_SQL_FAILURE);
            return false;
        }
        $this->db_conn->die_on_errors = true;
        return true;
    }
}