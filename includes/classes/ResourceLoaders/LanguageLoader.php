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
 * @since ZC v1.5.7
 */
class Language_Loader
{
    private array $language_files_loaded;
    public function __construct(private $array_loader, private $file_loader)
    {
        $this->language_files_loaded = ['arrays' => [], 'legacy' => []];
    }
    /**
     * @since ZC v1.5.8
     */
    public function load_initial_language_defines(): void
    {
        $this->array_loader->load_initial_language_defines($this);
        $this->file_loader->load_initial_language_defines($this);
    }
    /**
     * @since ZC v1.5.8
     */
    public function finalize_language_defines(): void
    {
        $this->array_loader->make_constants($this->array_loader->get_language_defines());
    }
    /**
     * @since ZC v1.5.8
     */
    public function get_language_files_loaded(): array
    {
        return $this->language_files_loaded;
    }
    /**
     * @since ZC v1.5.8
     */
    public function add_language_files_loaded(string $type, string $define_file): void
    {
        $this->language_files_loaded[$type][] = $define_file;
    }
    /**
     * @since ZC v1.5.8
     */
    public function load_defines_from_file(string $base_directory, string $language, string $language_file): bool
    {
        $this->array_loader->load_defines_from_array_file($base_directory, $language, $language_file);
        $this->file_loader->load_file_define_file(DIR_FS_CATALOG . DIR_WS_LANGUAGES . $language . $base_directory . '/' . $language_file);
        return true;
    }
    /**
     * @since ZC v1.5.8
     */
    public function load_module_defines_from_file(string $base_directory, string $language, string $module_type, string $language_file): bool
    {
        $defs = $this->array_loader->load_module_defines_from_array_file(DIR_FS_CATALOG . 'includes/languages/', $language, $module_type, $language_file);
        $this->array_loader->make_constants($defs);
        if ($module_type !== '') {
            $module_type .= '/';
        }
        $this->file_loader->load_file_define_file(DIR_FS_CATALOG . DIR_WS_LANGUAGES . $language . $base_directory . $module_type . $language_file);
        return true;
    }
    /**
     * @since ZC v2.1.0
     */
    public function make_catalog_array_constants(string $file_name, string $extra_dir = ''): void
    {
        $this->array_loader->make_catalog_array_constants($file_name, $extra_dir);
    }
    /**
     * Used on the catalog-side to set the current page for the language-load, since it's not necessarily
     * available during the autoload process (e.g. for AJAX handlers).
     *
     * @since ZC v1.5.8
     */
    public function set_current_page(string $current_page): void
    {
        $this->array_loader->current_page = $current_page;
        $this->file_loader->current_page = $current_page;
    }
    /**
     * @since ZC v1.5.7
     */
    public function load_language_for_view(): void
    {
        $this->array_loader->load_language_for_view();
        $this->file_loader->load_language_for_view();
    }
    /**
     * @since ZC v1.5.8
     */
    public function load_extra_language_files(string $root_path, string $language, string $file_name, string $extra_path = ''): void
    {
        $this->array_loader->load_extra_language_files($root_path, $language, $file_name, $extra_path);
        $this->file_loader->load_extra_language_files($root_path, $language, $file_name, $extra_path);
    }
    /**
     * @since ZC v1.5.8
     */
    public function has_language_file(string $root_path, string $language, string $file_name, string $extra_path = ''): bool
    {
        if (is_file($root_path . $language . $extra_path . '/' . $file_name)) {
            return true;
        }
        if (is_file($root_path . $language . $extra_path . '/lang.' . $file_name)) {
            return true;
        }
        return false;
    }
    /**
     * @since ZC v2.1.0
     */
    public function load_module_language_file(string $file_name, string $module_type): bool
    {
        $this->array_loader->load_module_language_file($file_name, $module_type);
        $this->file_loader->load_module_language_file($file_name, $module_type);
        $language_files_loaded = array_merge($this->language_files_loaded['arrays'], $this->language_files_loaded['legacy']);
        if ($module_type !== '') {
            $module_type .= '/';
        }
        $match_string = '~modules/' . $module_type . '(lang\.)?' . $file_name . '$~';
        $match_string_template = '~modules/' . $module_type . $this->array_loader->get_template_dir() . '/(lang\.)?' . $file_name . '$~';
        foreach ($language_files_loaded as $next_file) {
            if (preg_match($match_string, (string) $next_file) || preg_match($match_string_template, (string) $next_file)) {
                return true;
            }
        }
        return false;
    }
    /**
     * @since ZC v1.5.8
     */
    public function is_file_already_loaded(string $define_file): bool
    {
        $file_info = pathinfo($define_file);
        $search_file = $file_info['basename'];
        if (!str_starts_with($search_file, 'lang.')) {
            $search_file = 'lang.' . $search_file;
        }
        $search_file = $file_info['dirname'] . '/' . $search_file;
        if (in_array($search_file, $this->language_files_loaded['arrays'])) {
            return true;
        }
        if (in_array($define_file, $this->language_files_loaded['legacy'])) {
            return true;
        }
        return false;
    }
}