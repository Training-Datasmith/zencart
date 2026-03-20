<?php

declare (strict_types=1);
/**
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
namespace Zencart\Init_System;

/**
 * @since ZC v1.5.7
 */
class Init_System
{
    private bool $debug;
    private array $debug_list;
    private array $action_list;
    public function __construct(private readonly string $context, private readonly string $loader_prefix, private $file_system, private $plugin_manager, private $installed_plugins)
    {
        $this->debug = false;
        $this->debug_list = [];
        $this->action_list = [];
    }
    /**
     * @since ZC v1.5.7
     */
    public function load_auto_loaders(): array
    {
        $core_loader_list = $this->load_auto_loaders_from_system('core', DIR_WS_INCLUDES . 'auto_loaders');
        $plugin_loader_list = $this->load_plugin_auto_loaders('plugin');
        return $this->merge_auto_loaders($core_loader_list, $plugin_loader_list);
    }
    /**
     * @since ZC v1.5.7
     */
    public function set_debug(bool $debug = false): void
    {
        $this->debug = $debug;
    }
    /**
     * @since ZC v1.5.7
     */
    public function process_loader_list(array $loader_list): array
    {
        ksort($loader_list);
        foreach ($loader_list as $action_point => $entries) {
            $this->debug_list[] = '##################################################################';
            $this->debug_list[] = 'Action Point - ' . $action_point;
            $this->process_action_point_entries($entries);
        }
        if ($this->debug) {
            echo 'function processLoaderList:<pre>';
            print_r($this->debug_list);
            echo '</pre>';
        }
        return $this->action_list;
    }
    /**
     * @since ZC v1.5.7
     */
    protected function process_action_point_entries(array $entries): void
    {
        foreach ($entries as $entry) {
            if (!isset($entry['forceLoad'])) {
                $entry['forceLoad'] = false;
            }
            $this->process_action_point_entry($entry);
            $this->debug_list[] = '=================================================================';
        }
    }
    /**
     * @since ZC v1.5.7
     */
    protected function process_action_point_entry(array $entry): void
    {
        $auto_type_method = 'processAutoType' . ucfirst((string) $entry['autoType']);
        $this->debug_list[] = 'Auto Type Method - ' . $auto_type_method;
        if (!method_exists($this, $auto_type_method)) {
            return;
        }
        $this->{$auto_type_method}($entry);
    }
    /**
     * @since ZC v1.5.7
     */
    protected function process_auto_type_class(array $entry): void
    {
        $file_path = DIR_FS_CATALOG . DIR_WS_CLASSES;
        if (isset($entry['classPath'])) {
            $file_path = $entry['classPath'];
        }
        if ($entry['loaderType'] === 'plugin') {
            $file_path = $this->find_plugin_directory($entry['classPath'] ?? DIR_WS_CLASSES, $entry['pluginInfo']['unique_key']);
        }
        $this->debug_list[] = 'processing class - ' . $file_path . $entry['loadFile'];
        $result = 'FAILED';
        if (file_exists($file_path . $entry['loadFile'])) {
            $result = 'SUCCESS';
            $this->action_list[] = ['type' => 'include', 'filePath' => $file_path . $entry['loadFile'], 'forceLoad' => $entry['forceLoad']];
        }
        $this->debug_list[] = 'loading class - ' . $file_path . $entry['loadFile'] . ' - ' . $result;
    }
    /**
     * @param $entry
     * @todo should deprecate session bound classes.
     * @since ZC v1.5.7
     */
    protected function process_auto_type_class_instantiate(array $entry): void
    {
        $object_name = $entry['objectName'];
        $class_name = $entry['className'];
        $this->debug_list[] = 'processing class instantiate - class = ' . $class_name . ' object name = ' . $object_name;
        $class_session = isset($entry['classSession']) && $entry['classSession'] === true;
        $check_instantiated = isset($entry['checkInstantiated']) && $entry['checkInstantiated'] === true;
        if (!$class_session) {
            $this->debug_list[] = 'instantiating normal class - ' . $class_name . ' as ' . $object_name;
            $this->action_list[] = ['type' => 'class', 'object' => $object_name, 'class' => $class_name];
            return;
        }
        $this->debug_list[] = 'instantiating session bound class - ' . $class_name . ' as ' . $object_name;
        $this->action_list[] = ['type' => 'sessionClass', 'object' => $object_name, 'class' => $class_name, 'checkInstantiated' => $check_instantiated];
    }
    /**
     * @since ZC v1.5.7
     */
    protected function process_auto_type_object_method(array $entry): void
    {
        $object_name = $entry['objectName'];
        $method_name = $entry['methodName'];
        $this->debug_list[] = 'processing object method - ' . $object_name . ' => ' . $method_name;
        $this->action_list[] = ['type' => 'objectMethod', 'object' => $object_name, 'method' => $method_name];
    }
    /**
     * @since ZC v1.5.7
     */
    protected function process_auto_type_require(array $entry): void
    {
        $file_path = $entry['loadFile'];
        $this->debug_list[] = 'processing require - ' . $entry['loadFile'];
        $result = 'FAILED';
        if (file_exists($file_path)) {
            $result = 'SUCCESS';
            $this->action_list[] = ['type' => 'require', 'filePath' => $file_path, 'forceLoad' => $entry['forceLoad']];
        }
        $this->debug_list[] = 'loading require - ' . $file_path . ' - ' . $result;
    }
    /**
     * @since ZC v1.5.7
     */
    protected function process_auto_type_include(array $entry): void
    {
        $file_path = $entry['loadFile'];
        $this->debug_list[] = 'processing include - ' . $entry['loadFile'];
        $result = 'FAILED';
        if (file_exists($file_path)) {
            $result = 'SUCCESS';
            $this->action_list[] = ['type' => 'include', 'filePath' => $file_path, 'forceLoad' => $entry['forceLoad']];
        }
        $this->debug_list[] = 'loading include - ' . $file_path . ' - ' . $result;
    }
    /**
     * @since ZC v1.5.7
     */
    protected function process_auto_type_init_script(array $entry): void
    {
        $actual_dir = DIR_WS_INCLUDES . 'init_includes/';
        if ($entry['loaderType'] == 'plugin') {
            $actual_dir = $this->find_plugin_directory($actual_dir, $entry['pluginInfo']['unique_key']);
        }
        if (file_exists($actual_dir . 'overrides/' . $entry['loadFile'])) {
            $actual_dir = $actual_dir . 'overrides/';
        }
        $this->action_list[] = ['type' => 'require', 'filePath' => $actual_dir . $entry['loadFile'], 'forceLoad' => $entry['forceLoad']];
        $this->debug_list[] = 'loading init_script - ' . $actual_dir . $entry['loadFile'];
    }
    /**
     * @since ZC v1.5.7
     */
    protected function load_auto_loaders_from_system(string $loader_type, string $root_dir, $plugin = []): array
    {
        $file_list = $this->file_system->list_files_from_directory_alpha_sorted($root_dir);
        $file_list = $this->process_for_overrides($loader_type, $file_list, $root_dir);
        $loader_list = $this->get_loaders_from_file_list($file_list);
        return $this->process_loader_list_for_type($loader_type, $loader_list, $plugin);
    }
    /**
     * @since ZC v1.5.7
     */
    protected function load_plugin_auto_loaders(string $loader_type): array
    {
        $plugin_loader_list = [];
        foreach ($this->installed_plugins as $plugin) {
            $base_dir = $this->plugin_manager->get_plugin_version_directory($plugin['unique_key'], $this->installed_plugins);
            $root_dir = $base_dir . $this->context . '/includes/auto_loaders';
            $loader_list = $this->load_auto_loaders_from_system($loader_type, $root_dir, $plugin);
            $plugin_loader_list = $this->merge_auto_loaders($plugin_loader_list, $loader_list);
        }
        return $plugin_loader_list;
    }
    /**
     * @since ZC v1.5.7
     */
    protected function process_for_overrides(string $loader_type, array $file_list, string $root_dir): array
    {
        $new_file_list = [];
        $base_dir = $root_dir;
        $override_dir = $base_dir . '/overrides';
        $core_loader_file = '';
        if ($loader_type === 'core') {
            $core_loader_file = $this->loader_prefix . '.core.php';
            if ($this->override_file_exists($core_loader_file, $override_dir)) {
                $new_file_list[] = $override_dir . '/' . $core_loader_file;
            } else {
                $new_file_list[] = $base_dir . '/' . $core_loader_file;
            }
        }
        foreach ($file_list as $file) {
            if ($file === $core_loader_file) {
                continue;
            }
            if (!$this->file_matches_loader_prefix($file)) {
                continue;
            }
            $file_path = $base_dir . '/' . $file;
            if ($this->override_file_exists($file, $override_dir)) {
                $file_path = $override_dir . '/' . $file;
            }
            $new_file_list[] = $file_path;
        }
        return $new_file_list;
    }
    /**
     * @since ZC v1.5.7
     */
    protected function file_matches_loader_prefix(string $file): bool
    {
        $file_parts = explode('.', $file);
        if (($file_parts[0] ?? '') !== $this->loader_prefix) {
            return false;
        }
        return true;
    }
    /**
     * @since ZC v1.5.7
     */
    protected function override_file_exists(string $file, string $override_dir): bool
    {
        return file_exists($override_dir . '/' . $file);
    }
    /**
     * @since ZC v1.5.7
     */
    protected function get_loaders_from_filelist(array $file_list): array
    {
        $auto_load_config = [];
        foreach ($file_list as $file) {
            require $file;
        }
        return $auto_load_config;
    }
    /**
     * @since ZC v1.5.7
     */
    protected function process_loader_list_for_type(string $type, array $loader_list, $plugin = []): array
    {
        $new_list = [];
        foreach ($loader_list as $break_point => $loaders) {
            foreach ($loaders as $key => $loader) {
                $loader['loaderType'] = $type;
                $loader['pluginInfo'] = $plugin;
                $new_list[$break_point][$key] = $loader;
            }
        }
        return $new_list;
    }
    /**
     * @since ZC v1.5.7
     */
    protected function merge_auto_loaders(array $core_loaders, array $plugin_loaders): array
    {
        foreach ($plugin_loaders as $breakpoint => $plugin_loader_for_breakpoint) {
            if (array_key_exists($breakpoint, $core_loaders)) {
                $core_loaders = $this->add_plugin_loader_to_break_point($breakpoint, $core_loaders, $plugin_loader_for_breakpoint);
            } else {
                $core_loaders[$breakpoint] = $plugin_loader_for_breakpoint;
            }
        }
        return $core_loaders;
    }
    /**
     * @since ZC v1.5.7
     */
    protected function add_plugin_loader_to_break_point($breakpoint, array $core_loaders, array $plugin_loader_for_breakpoint): array
    {
        foreach ($plugin_loader_for_breakpoint as $plugin_loader) {
            $core_loaders[$breakpoint][] = $plugin_loader;
        }
        return $core_loaders;
    }
    /**
     * @since ZC v1.5.7
     */
    protected function find_plugin_directory(string $file_path, string $plugin_name): string
    {
        $rel_dir = $this->file_system->get_relative_dir($file_path);
        $plugin_dir = $this->plugin_manager->get_plugin_version_directory($plugin_name, $this->installed_plugins);
        return $plugin_dir . $this->context . '/' . $rel_dir;
    }
}