<?php

declare (strict_types=1);
/**
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Oct 25 Modified in v2.2.0 $
 */
namespace Zencart\Plugin_Manager;

use Zencart\Db_Repositories\Plugin_Control_Repository;
use Zencart\Db_Repositories\Plugin_Control_Version_Repository;
use Zencart\Plugin_Support\Plugin_Status;
/**
 * @since ZC v1.5.7
 */
class Plugin_Manager
{
    public function __construct(private readonly Plugin_Control_Repository $plugin_control, private readonly Plugin_Control_Version_Repository $plugin_control_version)
    {
    }
    /**
     * @since ZC v1.5.7
     */
    public function inspect_and_update(): void
    {
        $plugins_from_filesystem = $this->get_plugins_from_file_system();
        $this->update_db_plugins($plugins_from_filesystem);
    }
    /**
     * @since ZC v1.5.7
     */
    public function get_installed_plugins(): array
    {
        return $this->plugin_control->get_installed_plugins(Plugin_Status::ENABLED);
    }
    /**
     * @since ZC v1.5.7
     */
    public function get_plugin_version_directory(string $plugin_name, array $installed_plugins): ?string
    {
        if (!array_key_exists($plugin_name, $installed_plugins)) {
            return null;
        }
        return DIR_FS_CATALOG . 'zc_plugins/' . $plugin_name . '/' . $installed_plugins[$plugin_name]['version'] . '/';
    }
    /**
     * @since ZC v1.5.7
     */
    public function is_upgrade_available(string $unique_key, string $current_version): bool|int|null
    {
        if (empty($current_version)) {
            return false;
        }
        $version_list = $this->get_versions_for_upgrade($unique_key, $current_version);
        return count($version_list);
    }
    /**
     * @since ZC v1.5.7
     */
    public function get_versions_for_upgrade(string $unique_key, string $current_version): array
    {
        if (empty($current_version)) {
            return [];
        }
        $versions = $this->get_plugin_versions($unique_key);
        $version_list = [];
        foreach ($versions as $version) {
            if (version_compare($version['version'], $current_version, '<=')) {
                continue;
            }
            $version_list[$version['version']] = $version['version'];
        }
        return $version_list;
    }
    /**
     * @since ZC v2.0.0
     */
    public function is_new_download_available(int|string|null $plugin_id, string $current_version): bool|array
    {
        if (empty($plugin_id)) {
            return false;
        }
        return plugin_version_check_for_updates($plugin_id, $current_version);
    }
    /**
     * @since ZC v1.5.7
     */
    public function get_plugins_after_checking_for_new_versions_online(): bool|array
    {
        $plugins = $this->get_plugins_from_db();
        // new array for reverse-lookup after getting results back
        $plugins_by_id = [];
        $ids_csv = '';
        foreach ($plugins as $plugin) {
            $plugins_by_id[$plugin['zc_contrib_id']] = $plugin;
            $ids_csv .= (int) trim((string) $plugin['zc_contrib_id']) . ',';
        }
        $results = $this->get_latest_plugin_versions_online($ids_csv);
        // if no results or invalid format, abort
        // @TODO - is this the right return type? or should we return the unaltered $plugins array?
        if (empty($results)) {
            return false;
        }
        // make sure $results is the actual array we want to iterate over, and not a sub-array
        if (is_array($results) && !isset($results[0]['id']) && isset($results[0][0]['id'])) {
            $results = $results[0];
        }
        if (!isset($results[0]['id'])) {
            return false;
            // @TODO or return original $plugins array?
        }
        $present_zc_version = 'v' . preg_replace('/[^0-9.]/', '', zen_get_zcversion());
        foreach ($results as $result) {
            $unique_key = $plugins_by_id[$result['id']]['unique_key'];
            if (version_compare($plugins_by_id[$result['id']]['version'], $result['latest_plugin_version'], '<')) {
                $plugins[$unique_key]['new_online_version_exists'] = true;
                $plugins[$unique_key]['latest_plugin_version'] = $result['latest_plugin_version'];
                $plugins[$unique_key]['zcversions'] = $result['zcversions'];
                if (in_array($present_zc_version, $result['zcversions'], $strict = false)) {
                    $plugins[$unique_key]['new_plugin_exists_for_this_zc_version'] = true;
                }
            }
        }
        return $plugins;
    }
    /**
     * @since ZC v1.5.7
     */
    protected function get_latest_plugin_versions_online(string $plugin_ids_csv = '0'): array|false
    {
        if (empty(trim($plugin_ids_csv, ','))) {
            return false;
        }
        $version_server = new \Version_Server();
        $data = json_decode($version_server->get_plugin_version($plugin_ids_csv), true);
        if (null === $data || isset($data['error'])) {
            if (LOG_PLUGIN_VERSIONCHECK_FAILURES) {
                error_log('CURL error checking plugin versions (in batch): ' . print_r(!empty($data) ? $data : 'null', true));
            }
            return false;
        }
        if (!is_array($data)) {
            try {
                $data = json_decode((string) $data, true);
            } catch (\Exception) {
                if (LOG_PLUGIN_VERSIONCHECK_FAILURES) {
                    error_log('CURL error checking plugin versions (in batch): ' . print_r(!empty($data) ? $data : 'null', true));
                }
                return false;
            }
        }
        return $data;
    }
    /**
     * @since ZC v1.5.7
     */
    protected function get_plugin_versions(string $unique_key): array
    {
        return $this->plugin_control_version->get_by_unique_key($unique_key);
    }
    /**
     * @since ZC v1.5.7
     */
    protected function get_plugins_from_file_system(): array
    {
        $plugin_dir = DIR_FS_CATALOG . 'zc_plugins';
        $plugin_list = [];
        if (!is_dir($plugin_dir)) {
            return $plugin_list;
        }
        $dir = new \Directory_Iterator($plugin_dir);
        foreach ($dir as $fileinfo) {
            if ($fileinfo->is_dot()) {
                continue;
            }
            if (!$fileinfo->is_dir()) {
                continue;
            }
            $version_info = $this->get_plugin_version_directories($fileinfo);
            if (count($version_info) === 0) {
                continue;
            }
            $plugin_list = $this->merge_in_version_info($plugin_list, $fileinfo->get_filename(), $version_info);
        }
        return $plugin_list;
    }
    /**
     * @since ZC v1.5.7
     */
    protected function get_plugin_version_directories(\Directory_Iterator $parent): array
    {
        $version_list = [];
        $dir = new \Directory_Iterator($parent->get_path_name());
        foreach ($dir as $fileinfo) {
            if ($fileinfo->is_dot()) {
                continue;
            }
            if (!$fileinfo->is_dir()) {
                continue;
            }
            if (!file_exists($fileinfo->get_pathname() . '/manifest.php')) {
                continue;
                //@todo consider throwing exception/trigger_error here
            }
            $manifest = require $fileinfo->get_pathname() . '/manifest.php';
            $version_list[$fileinfo->get_filename()] = $manifest;
            if ($_SESSION['languages_code'] !== 'en') {
                $this->load_plugin_language_constants($fileinfo->get_pathname());
            }
        }
        return $version_list;
    }
    /**
     * @since ZC v1.5.7
     */
    public function get_plugins_from_db(): array
    {
        return $this->plugin_control->get_all();
    }
    /**
     * @since ZC v1.5.7
     */
    protected function update_db_plugins(array $plugins_from_filesystem): void
    {
        $this->update_plugin_control($plugins_from_filesystem);
    }
    /**
     * @since ZC v1.5.7
     */
    protected function update_plugin_control(array $plugins_from_filesystem): void
    {
        // Mark all existing plugins as not found on filesystem
        $this->plugin_control->set_all_infs(0);
        $this->plugin_control_version->set_all_infs(0);
        $insert_values = [];
        $version_insert_values = [];
        foreach ($plugins_from_filesystem as $unique_key => $plugin) {
            $plugin_version = $plugin['versions'][0];
            $version_insert_values = $this->process_update_plugin_control_versions($unique_key, $plugins_from_filesystem, $version_insert_values);
            $insert_values[] = ['unique_key' => $unique_key, 'name' => $plugin[$plugin_version]['pluginName'], 'description' => $plugin[$plugin_version]['pluginDescription'], 'type' => '', 'status' => Plugin_Status::NOT_INSTALLED, 'author' => $plugin[$plugin_version]['pluginAuthor'], 'version' => '', 'zc_versions' => '', 'infs' => 1, 'zc_contrib_id' => $plugin[$plugin_version]['pluginId']];
        }
        // Insert new, and update existing, plugins
        $this->plugin_control->upsert_many($insert_values);
        $this->plugin_control_version->upsert_many($version_insert_values);
        // Remove any plugins no longer found on filesystem
        $this->plugin_control->delete_by_infs(0);
        $this->plugin_control_version->delete_by_infs(0);
    }
    /**
     * @since ZC v1.5.8
     */
    protected function process_update_plugin_control_versions(string $unique_key, array $plugins_from_filesystem, array $version_insert_values): array
    {
        $current_plugin = $plugins_from_filesystem[$unique_key];
        foreach ($current_plugin as $version => $version_info) {
            if ($version === 'versions') {
                continue;
            }
            $version_insert_values[] = ['unique_key' => $unique_key, 'author' => $version_info['pluginAuthor'], 'version' => $version, 'zc_versions' => json_encode($version_info['zcVersions']), 'infs' => 1];
        }
        return $version_insert_values;
    }
    /**
     * @since ZC v1.5.7
     */
    protected function merge_in_version_info(array $plugin_list, string $unique_key, array $version_info): array
    {
        $version_list = [];
        foreach ($version_info as $version => $detail) {
            $plugin_list[$unique_key][$version] = $detail;
            $version_list[] = $version;
        }
        usort($version_list, version_compare(...));
        $version_list = array_reverse($version_list);
        $plugin_list[$unique_key]['versions'] = $version_list;
        return $plugin_list;
    }
    /**
     * @since ZC v1.5.7
     */
    public function get_plugin_versions_for_plugin(string $unique_key): array
    {
        $results = $this->plugin_control_version->get_by_unique_key($unique_key);
        $versions = [];
        foreach ($results as $result) {
            $versions[$result['version']] = $result;
        }
        ksort($versions);
        return array_reverse($versions);
    }
    /**
     * @since ZC v1.5.7
     */
    public function get_plugin_versions_to_clean(string $unique_key, string $version): array
    {
        $versions = $this->get_plugin_versions_for_plugin($unique_key);
        unset($versions[$version]);
        return $versions;
    }
    /**
     * @since ZC v1.5.7
     */
    public function has_plugin_versions_to_clean(string $unique_key, string $version): ?int
    {
        return count($this->get_plugin_versions_to_clean($unique_key, $version));
    }
    /**
     * @since ZC v1.5.8
     */
    public function get_plugin_control(): Plugin_Control_Repository
    {
        return $this->plugin_control;
    }
    /**
     * @since ZC v2.2.0
     */
    protected function load_plugin_language_constants(string $pluginpath): void
    {
        $pluginpath = str_replace('\\', '/', $pluginpath);
        $file_path = [];
        foreach ($this->get_installed_plugins() as $plugin) {
            // make an array of all installed plugins paths
            $file_path[$plugin['unique_key']] = DIR_FS_CATALOG . 'zc_plugins/' . $plugin['unique_key'] . '/' . $plugin['version'];
        }
        if (!in_array($pluginpath, $file_path)) {
            $explodedpath = explode('/', $pluginpath);
            $pluginuniquekey = strtoupper($explodedpath[count($explodedpath) - 2]);
            // retrieve plugin's unique key
            $pluginconstantspath = $pluginpath . '/admin/includes/languages/' . $_SESSION['language'] . '/extra_definitions/lang.menu.php';
            // The language constant file 'lang.menu.php' must be in this folder
            if (is_file($pluginconstantspath)) {
                $pluginsconstants = require_once $pluginconstantspath;
                // Load language override constants definitions
                if (!is_array($pluginsconstants) || empty($pluginsconstants)) {
                    return;
                }
                $pluginnameconstant = 'ADMIN_PLUGIN_MANAGER_NAME_FOR_' . $pluginuniquekey;
                $plugindescriptionconstant = 'ADMIN_PLUGIN_MANAGER_DESCRIPTION_FOR_' . $pluginuniquekey;
                if (!defined($pluginnameconstant) && array_key_exists($pluginnameconstant, $pluginsconstants)) {
                    define($pluginnameconstant, $pluginsconstants[$pluginnameconstant]);
                }
                if (!defined($plugindescriptionconstant) && array_key_exists($plugindescriptionconstant, $pluginsconstants)) {
                    define($plugindescriptionconstant, $pluginsconstants[$plugindescriptionconstant]);
                }
            }
        }
    }
}