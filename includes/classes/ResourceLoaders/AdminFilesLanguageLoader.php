<?php

declare (strict_types=1);
/**
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
namespace Zencart\Language_Loader;

/**
 * @since ZC v1.5.8
 */
class Admin_Files_Language_Loader extends Files_Language_Loader
{
    /**
     * @since ZC v1.5.8
     */
    public function load_initial_language_defines($main_loader): void
    {
        $this->main_loader = $main_loader;
        $this->load_language_extra_definitions();
        $this->load_language_for_view();
        $this->load_base_language_file();
    }
    /**
     * @since ZC v1.5.8
     */
    protected function load_language_for_view()
    {
        $this->load_file_define_file(DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . $this->current_page);
    }
    /**
     * @since ZC v1.5.8
     */
    protected function load_language_extra_definitions()
    {
        $dir_path = DIR_WS_LANGUAGES . $_SESSION['language'] . '/extra_definitions';
        $file_list = $this->file_system->list_files_from_directory_alpha_sorted($dir_path, '~^(?!lang\.).*\.php$~i');
        foreach ($file_list as $file) {
            $this->load_file_define_file($dir_path . '/' . $file);
        }
    }
    /**
     * @since ZC v1.5.8
     */
    protected function load_base_language_file()
    {
        $this->load_file_define_file(DIR_WS_LANGUAGES . $_SESSION['language'] . '.php');
        $this->load_file_define_file(DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . FILENAME_EMAIL_EXTRAS);
        $this->load_file_define_file(zen_get_file_directory(DIR_FS_CATALOG_LANGUAGES . $_SESSION['language'] . '/', FILENAME_OTHER_IMAGES_NAMES));
    }
}