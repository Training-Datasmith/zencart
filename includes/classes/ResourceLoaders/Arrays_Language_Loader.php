<?php

declare (strict_types=1);
/**
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 29 Modified in v2.2.0 $
 */
namespace Zencart\Language_Loader;

/**
 * @since ZC v1.5.8
 */
class Arrays_Language_Loader extends Base_Language_Loader
{
    protected $main_loader;
    /**
     * @since ZC v1.5.8
     */
    public function make_constants($defines): bool
    {
        if (!is_array($defines)) {
            return false;
        }
        $constants_made = false;
        foreach ($defines as $define_key => $define_value) {
            if (defined($define_key)) {
                $constants_made = true;
                continue;
            }
            preg_match_all('/%{2}([^%]+)%{2}/', (string) $define_value, $matches, PREG_PATTERN_ORDER);
            if (count($matches[1])) {
                foreach ($matches[1] as $index => $match) {
                    if (isset($defines[$match])) {
                        $define_value = str_replace($matches[0][$index], $defines[$match], $define_value);
                    }
                }
            }
            define($define_key, $define_value);
            $constants_made = true;
        }
        return $constants_made;
    }
    /**
     * @since ZC v1.5.8
     */
    public function get_language_defines(): array
    {
        return $this->language_defines;
    }
    /**
     * @since ZC v1.5.8
     */
    protected function load_arrays_from_directory(string $root_path, string $language, string $extra_path): array
    {
        $path = $root_path . $language . $extra_path;
        $file_list = $this->file_system->list_files_from_directory($path, '~^lang\.(.*)\.php$~i');
        return $this->process_array_file_list($path, $file_list);
    }
    /**
     * @since ZC v1.5.8
     */
    protected function plugin_load_arrays_from_directory(string $language, string $extra_path, string $context = 'admin'): array
    {
        $define_list = [];
        foreach ($this->plugin_list as $plugin) {
            $plugin_dir = $this->zc_plugins_dir . $plugin['unique_key'] . '/' . $plugin['version'] . '/' . $context . '/includes/languages/';
            $defines = $this->load_arrays_from_directory($plugin_dir, $language, $extra_path);
            $define_list = array_merge($define_list, $defines);
        }
        return $define_list;
    }
    /**
     * @since ZC v1.5.8
     */
    protected function process_array_file_list(string $path, array $file_list): array
    {
        $define_list = [];
        foreach ($file_list as $file) {
            $defines = $this->load_array_define_file($path . '/' . $file);
            $define_list = array_merge($define_list, $defines);
        }
        return $define_list;
    }
    /**
     * @since ZC v1.5.8
     */
    public function load_extra_language_files(string $root_path, string $language, string $file_name, string $extra_path = ''): void
    {
        // -----
        // Any $extraPath specified, if not an empty string, must start with a '/' and not end with one.
        //
        $extra_path = trim($extra_path, '/');
        if ($extra_path !== '') {
            $extra_path = '/' . $extra_path;
        }
        $define_list_main = $this->load_defines_from_array_file($root_path, $language, $file_name, $extra_path);
        $extra_path .= '/' . $this->template_dir;
        $define_list_template = $this->load_defines_from_array_file($root_path, $language, $file_name, $extra_path);
        $define_list = array_merge($define_list_main, $define_list_template);
        $this->make_constants($define_list);
    }
    /**
     * @since ZC v2.1.0
     */
    public function load_module_language_file(string $file_name, string $module_type): bool
    {
        // -----
        // First, gather the 'base' 'english' language file for the given order_total/payment/shipping module. If
        // the current session's language is **other than** 'english', the file for that language (if present)
        // overwrites any of the 'english' language constants.
        //
        $define_list = $this->load_module_defines_from_array_file($this->fallback, $file_name, $module_type);
        if ($_SESSION['language'] !== $this->fallback) {
            $define_list = array_merge($define_list, $this->load_module_defines_from_array_file($_SESSION['language'], $file_name, $module_type));
        }
        // -----
        // Next, gather any 'english' language file from all zc_plugin's 'base' modules' directory; if the
        // current session's language is **other than** 'english', see if any file for that language is
        // provided by any enabled plugin.
        //
        // Any language definitions found in the plugins' files overwrite any previously-loaded ones.
        //
        $define_list_plugins = $this->plugin_load_defines_from_array_file($this->fallback, $file_name, 'catalog', '/modules/' . $module_type);
        $define_list = array_merge($define_list, $define_list_plugins);
        if ($_SESSION['language'] !== $this->fallback) {
            $define_list_plugins = $this->plugin_load_defines_from_array_file($_SESSION['language'], $file_name, 'catalog', '/modules/' . $module_type);
            $define_list = array_merge($define_list, $define_list_plugins);
        }
        // -----
        // Next, gather any 'english' language file from all zc_plugin's 'default' modules' directory; if the
        // current session's language is **other than** 'english', see if any file for that language is
        // provided by any enabled plugin.
        //
        // Any language definitions found in the plugins' files overwrite any previously-loaded ones.
        //
        $define_list_plugins = $this->plugin_load_defines_from_array_file($this->fallback, $file_name, 'catalog', '/modules/' . $module_type . '/default');
        $define_list = array_merge($define_list, $define_list_plugins);
        if ($_SESSION['language'] !== $this->fallback) {
            $define_list_plugins = $this->plugin_load_defines_from_array_file($_SESSION['language'], $file_name, 'catalog', '/modules/' . $module_type . '/default');
            $define_list = array_merge($define_list, $define_list_plugins);
        }
        // -----
        // Finally, gather any template-override definitions **for the current session language**. Any language
        // definitions found here overwrite any previously-loaded ones.
        //
        $define_list_template = $this->load_module_defines_from_array_file($_SESSION['language'], $file_name, $module_type, $this->template_dir . '/');
        $define_list = array_merge($define_list, $define_list_template);
        // -----
        // Create the language constants from the definitions found and return an indication of whether/not
        // constants were made (or pre-existing).
        //
        return $this->make_constants($define_list);
    }
    /**
     * @since ZC v1.5.8
     */
    protected function load_defines_from_array_file(string $root_path, string $language, string $file_name, string $extra_path = ''): array
    {
        $array_file_name = 'lang.' . $file_name;
        $main_file = $root_path . $language . $extra_path . '/' . $array_file_name;
        $fallback_file = $root_path . $language . '/' . $array_file_name;
        return $this->load_defines_with_fallback($main_file, $fallback_file);
    }
    /**
     * @since ZC v1.5.8
     */
    protected function load_module_defines_from_array_file(string $language, string $file_name, string $module_type, string $template_dir = ''): array
    {
        $root_path = DIR_FS_CATALOG . DIR_WS_LANGUAGES;
        $array_file_name = 'lang.' . $file_name;
        if ($module_type !== '') {
            $module_type .= '/';
        }
        $main_file = $root_path . $language . '/modules/' . $module_type . $template_dir . $array_file_name;
        $fallback_file = $root_path . $this->fallback . '/modules/' . $module_type . $template_dir . $array_file_name;
        return $this->load_defines_with_fallback($main_file, $fallback_file);
    }
    /**
     * @since ZC v1.5.8
     */
    protected function plugin_load_defines_from_array_file(string $language, string $file_name, string $context = 'admin', string $extra_path = ''): array
    {
        $define_list = [];
        foreach ($this->plugin_list as $plugin) {
            $plugin_dir = $this->zc_plugins_dir . $plugin['unique_key'] . '/' . $plugin['version'];
            $plugin_dir .= '/' . $context . '/includes/languages/';
            $plugin_define_list = $this->load_defines_from_array_file($plugin_dir, $language, $file_name, $extra_path);
            $define_list = array_merge($define_list, $plugin_define_list);
        }
        return $define_list;
    }
    /**
     * @since ZC v1.5.8
     */
    protected function load_defines_with_fallback(string $main_file, string $fallback_file): array
    {
        $define_list_fallback = [];
        if ($main_file !== $fallback_file) {
            $define_list_fallback = $this->load_array_define_file($fallback_file);
        }
        $define_list_main = $this->load_array_define_file($main_file);
        return array_merge($define_list_fallback, $define_list_main);
    }
    /**
     * @since ZC v1.5.8
     */
    protected function add_language_defines($define_list): void
    {
        if (!is_array($define_list)) {
            return;
        }
        $new_define_list = array_merge($this->language_defines, $define_list);
        $this->language_defines = $new_define_list;
    }
    /**
     * @since ZC v1.5.8
     */
    protected function load_array_define_file(string $defines_file): array
    {
        if ($this->main_loader->is_file_already_loaded($defines_file) === true || !is_file($defines_file)) {
            return [];
        }
        $this->main_loader->add_language_files_loaded('arrays', $defines_file);
        // file should return a variable
        $defines_list = require $defines_file;
        return $defines_list;
    }
    // -----
    // Loads the specified file from the specified directory, with language fall-back.
    //
    // First the file from the 'fallback' (i.e. 'english') language subdirectory is loaded and
    // its definitions added to the to-be-generated constants' list.
    //
    // Next, if the current session language is different than the 'fallback', load the file from
    // the session-specified directory and add its definitions to the to-be-generated constants' list,
    // overwriting any previous definitions.
    //
    /**
     * @since ZC v2.1.0
     */
    protected function load_defines_from_dir_file_with_fallback(string $directory, string $filename): void
    {
        $define_list = $this->load_defines_from_array_file($directory, $this->fallback, $filename);
        $this->add_language_defines($define_list);
        if ($_SESSION['language'] !== $this->fallback) {
            $define_list = $this->load_defines_from_array_file($directory, $_SESSION['language'], $filename);
            $this->add_language_defines($define_list);
        }
    }
    // -----
    // Load (and make associated constants) for a given **storefront** language file.  Used
    // primarily by admin plugins that have common admin/storefront constant definitions.
    //
    // Note: The $extraDir, if non-blank, must start with a '/' and not end with one!
    //
    /**
     * @since ZC v2.1.0
     */
    public function make_catalog_array_constants(string $file_name, string $extra_dir = ''): void
    {
        if (str_starts_with($file_name, 'lang.') === false) {
            $file_name = 'lang.' . $file_name;
        }
        $root_dir = DIR_FS_CATALOG . DIR_WS_LANGUAGES;
        $main_file = $root_dir . $_SESSION['language'] . $extra_dir . '/' . $file_name;
        $fallback_file = $root_dir . $this->fallback . $extra_dir . '/' . $file_name;
        $define_list = $this->load_defines_with_fallback($main_file, $fallback_file);
        foreach ($this->plugin_list as $plugin) {
            $plugin_dir = $this->zc_plugins_dir . $plugin['unique_key'] . '/' . $plugin['version'];
            $plugin_dir .= '/catalog/includes/languages/';
            $main_file = $plugin_dir . $_SESSION['language'] . $extra_dir . '/' . $file_name;
            $fallback_file = $plugin_dir . $this->fallback . $extra_dir . '/' . $file_name;
            $plugin_define_list = $this->load_defines_with_fallback($main_file, $fallback_file);
            $define_list = array_merge($define_list, $plugin_define_list);
        }
        $template_file = $root_dir . $_SESSION['language'] . $extra_dir . '/' . $this->template_dir . '/' . $file_name;
        $define_list = array_merge($define_list, $this->load_array_define_file($template_file));
        $this->make_constants($define_list);
    }
}