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
class Catalog_Files_Language_Loader extends Files_Language_Loader
{
    /**
     * @since ZC v1.5.8
     */
    public function load_initial_language_defines($main_loader): void
    {
        $this->main_loader = $main_loader;
        $this->load_language_extra_definitions();
        $this->load_main_language_files();
    }
    /**
     * @since ZC v1.5.8
     */
    public function load_language_for_view(): void
    {
        $directory = DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . $this->template_dir;
        if (defined('NO_LANGUAGE_SUBSTRING_MATCH') && in_array($this->current_page, NO_LANGUAGE_SUBSTRING_MATCH)) {
            $files_to_match = $this->current_page;
        } else {
            $files_to_match = $this->current_page . '(.*)';
        }
        $files = $this->file_system->list_files_from_directory_alpha_sorted($directory, '~^' . $files_to_match . '\.php$~i');
        foreach ($files as $file) {
            $this->load_file_define_file($directory . '/' . $file);
        }
        $directory = DIR_WS_LANGUAGES . $_SESSION['language'];
        $files = $this->file_system->list_files_from_directory_alpha_sorted($directory, '~^' . $files_to_match . '\.php$~i');
        foreach ($files as $file) {
            $this->load_file_define_file($directory . '/' . $file);
        }
    }
    /**
     * @since ZC v1.5.8
     */
    protected function load_main_language_files(): void
    {
        $extra_files = [FILENAME_EMAIL_EXTRAS, FILENAME_HEADER, FILENAME_BUTTON_NAMES, FILENAME_ICON_NAMES, FILENAME_OTHER_IMAGES_NAMES, FILENAME_CREDIT_CARDS, FILENAME_WHOS_ONLINE, FILENAME_META_TAGS];
        $this->load_file_define_file(DIR_WS_LANGUAGES . $this->template_dir . '/' . $_SESSION['language'] . '.php');
        $this->load_file_define_file(DIR_WS_LANGUAGES . $_SESSION['language'] . '.php');
        foreach ($extra_files as $file) {
            $file = basename($file, '.php') . '.php';
            $this->load_extra_language_files(DIR_WS_LANGUAGES, $_SESSION['language'], $file);
        }
    }
    /**
     * @since ZC v1.5.8
     */
    protected function load_language_extra_definitions(): void
    {
        $extra_defs_dir = DIR_WS_LANGUAGES . $_SESSION['language'] . '/extra_definitions';
        $extra_defs_dir_tpl = $extra_defs_dir . '/' . $this->template_dir;
        $extra_defs = $this->file_system->list_files_from_directory_alpha_sorted($extra_defs_dir);
        $extra_defs_tpl = $this->file_system->list_files_from_directory_alpha_sorted($extra_defs_dir_tpl);
        $folder_list = [$extra_defs_dir => $extra_defs, $extra_defs_dir_tpl => $extra_defs_tpl];
        $found_list = [];
        foreach ($folder_list as $folder => $entries) {
            foreach ($entries as $entry) {
                $found_list[$entry] = $folder;
            }
        }
        foreach ($found_list as $file => $directory) {
            $this->load_file_define_file($directory . '/' . $file);
        }
    }
}