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
class Admin_Arrays_Language_Loader extends Arrays_Language_Loader
{
    /**
     * @since ZC v1.5.8
     */
    public function load_initial_language_defines($main_loader): void
    {
        $this->main_loader = $main_loader;
        $this->load_base_language_files();
        $this->load_language_for_view();
        $this->load_language_extra_definitions();
    }
    /**
     * @since ZC v1.5.8
     */
    protected function load_language_for_view(): void
    {
        $this->load_defines_from_dir_file_with_fallback(DIR_WS_LANGUAGES, $this->current_page);
        $define_list = $this->plugin_load_defines_from_array_file($this->fallback, $this->current_page, 'admin', '');
        $this->add_language_defines($define_list);
        if ($_SESSION['language'] !== $this->fallback) {
            $define_list = $this->plugin_load_defines_from_array_file($_SESSION['language'], $this->current_page, 'admin', '');
            $this->add_language_defines($define_list);
        }
    }
    /**
     * @since ZC v1.5.8
     */
    protected function load_language_extra_definitions(): void
    {
        $define_list = $this->load_arrays_from_directory(DIR_WS_LANGUAGES, $this->fallback, '/extra_definitions');
        $this->add_language_defines($define_list);
        if ($_SESSION['language'] !== $this->fallback) {
            $define_list = $this->load_arrays_from_directory(DIR_WS_LANGUAGES, $_SESSION['language'], '/extra_definitions');
            $this->add_language_defines($define_list);
        }
        $define_list = $this->plugin_load_arrays_from_directory($this->fallback, '/extra_definitions');
        $this->add_language_defines($define_list);
        if ($_SESSION['language'] !== $this->fallback) {
            $define_list = $this->plugin_load_arrays_from_directory($_SESSION['language'], '/extra_definitions');
            $this->add_language_defines($define_list);
        }
    }
    /**
     * @since ZC v2.1.0
     */
    protected function load_base_language_files()
    {
        // -----
        // First, load the main language file(). The 'lang.english.php' file is always
        // loaded, with its constant values possibly overwritten by a different main
        // language file (e.g. lang.spanish.php).
        //
        // These definitions are added to the to-be-generated constants' list.
        //
        $main_file = DIR_WS_LANGUAGES . 'lang.' . $_SESSION['language'] . '.php';
        $fallback_file = DIR_WS_LANGUAGES . 'lang.' . $this->fallback . '.php';
        $define_list = $this->load_defines_with_fallback($main_file, $fallback_file);
        $this->add_language_defines($define_list);
        // -----
        // Next, load some other files with multi-page-use constants, adding their
        // definitions to the to-be-generated constants' list.
        //
        // Each file is first loaded from the 'english' sub-directory for the given
        // directory and then, if the session's language is non-english, overwritten
        // by any such file found in that language sub-directory (e.g. 'spanish').
        //
        $this->load_defines_from_dir_file_with_fallback(DIR_WS_LANGUAGES, 'gv_name.php');
        $this->load_defines_from_dir_file_with_fallback(DIR_WS_LANGUAGES, FILENAME_EMAIL_EXTRAS);
        $this->load_defines_from_dir_file_with_fallback(DIR_FS_CATALOG . DIR_WS_LANGUAGES, FILENAME_OTHER_IMAGES_NAMES);
        // -----
        // Finally, if the 'lang.other_images_names.php' has a template-override file **in the
        // current session's language**, load those definitions, adding to the
        // to-be-generated constants' list.
        //
        if ($this->file_system->has_template_language_override($this->template_dir, DIR_FS_CATALOG . DIR_WS_LANGUAGES, $_SESSION['language'], FILENAME_OTHER_IMAGES_NAMES)) {
            $define_list = $this->load_defines_from_array_file(DIR_FS_CATALOG . DIR_WS_LANGUAGES, $_SESSION['language'], FILENAME_OTHER_IMAGES_NAMES, '/' . $this->template_dir);
            $this->add_language_defines($define_list);
        }
    }
}