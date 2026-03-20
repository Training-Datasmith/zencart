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
class Files_Language_Loader extends Base_Language_Loader
{
    protected $main_loader;
    /**
     * @since ZC v1.5.8
     */
    public function load_extra_language_files(string $root_path, string $language, string $file_name, string $extra_path = ''): void
    {
        if ($this->main_loader->has_language_file($root_path, $language, $file_name, $extra_path . '/' . $this->template_dir)) {
            $this->load_file_define_file($root_path . $language . $extra_path . '/' . $this->template_dir . '/' . $file_name);
        } else {
            $this->load_file_define_file($root_path . $language . $extra_path . '/' . $file_name);
        }
    }
    /**
     * @since ZC v2.1.0
     */
    public function load_module_language_file(string $file_name, string $module_type): bool
    {
        $root_path = DIR_FS_CATALOG . DIR_WS_LANGUAGES . $_SESSION['language'];
        if ($module_type !== '') {
            $module_type .= '/';
        }
        $extra_path = '/modules/' . $module_type;
        if ($this->load_file_define_file($root_path . $extra_path . $this->template_dir . '/' . $file_name) === true) {
            return true;
        }
        return $this->load_file_define_file($root_path . $extra_path . $file_name);
    }
    /**
     * @since ZC v1.5.8
     */
    protected function load_file_define_file(string $define_file): bool
    {
        $path_info = pathinfo($define_file);
        if (preg_match('~^lang\.~i', $path_info['basename'])) {
            return false;
        }
        if (!is_file($define_file)) {
            return false;
        }
        if ($this->main_loader->is_file_already_loaded($define_file)) {
            return false;
        }
        $this->main_loader->add_language_files_loaded('legacy', $define_file);
        include_once $define_file;
        return true;
    }
}