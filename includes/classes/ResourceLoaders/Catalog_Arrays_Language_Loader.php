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
class Catalog_Arrays_Language_Loader extends Arrays_Language_Loader
{
    /**
     * @since ZC v1.5.8
     */
    public function load_initial_language_defines($main_loader): void
    {
        $this->main_loader = $main_loader;
        $this->load_main_language_files();
        $this->load_language_extra_definitions();
    }
    /**
     * @since ZC v1.5.8
     */
    public function load_language_for_view(): void
    {
        // -----
        // First, load all the array files for the current page, creating the
        // constants for the 'base' current-page's file.
        //
        $this->load_current_page_base_file();
        // -----
        // Next, build up the constant-definition array for additional 'base' per-page
        // language files, i.e. files that are 'similar' to the current page's name
        // but not specifically the 'base' current-page file.
        //
        // Start with any such files in the 'english' language directory.  If the current
        // session language is different than 'english', load those files, overwriting any
        // similarly-named definitions present in 'english'.
        //
        $defines_list = $this->load_current_page_extra_files_from_dir(DIR_WS_LANGUAGES . $this->fallback);
        if ($_SESSION['language'] !== $this->fallback) {
            $defines_list = array_merge($defines_list, $this->load_current_page_extra_files_from_dir(DIR_WS_LANGUAGES . $_SESSION['language']));
        }
        // -----
        // Bring in any additional per-page files from enabled zc_plugins.
        //
        // Any definitions found in these directories overwrite any of the 'base' per-page
        // definitions.
        //
        foreach ($this->plugin_list as $plugin) {
            $plugin_dir = $this->zc_plugins_dir . $plugin['unique_key'] . '/' . $plugin['version'] . '/catalog/includes/languages/';
            $defines_list_plugin = $this->load_current_page_extra_files_from_dir($plugin_dir . $this->fallback);
            if ($_SESSION['language'] !== $this->fallback) {
                $defines_list_plugin = array_merge($defines_list_plugin, $this->load_current_page_extra_files_from_dir($plugin_dir . $_SESSION['language']));
            }
            $defines_list = array_merge($defines_list, $defines_list_plugin);
            $defines_list_plugin = $this->load_current_page_extra_files_from_dir($plugin_dir . $this->fallback . '/default');
            if ($_SESSION['language'] !== $this->fallback) {
                $defines_list_plugin = array_merge($defines_list_plugin, $this->load_current_page_extra_files_from_dir($plugin_dir . $_SESSION['language'] . '/default'));
            }
            $defines_list = array_merge($defines_list, $defines_list_plugin);
        }
        // -----
        // Finally, if there are additional per-page files in the current language's active template's
        // directory, those overwrite any definitions previously loaded.
        //
        $defines_list_template = $this->load_current_page_extra_files_from_dir(DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . $this->template_dir);
        $defines_list = array_merge($defines_list, $defines_list_template);
        // -----
        // Create language constants from the definitions loaded here.
        //
        $this->make_constants($defines_list);
    }
    /**
     * @since ZC v2.1.0
     */
    protected function load_current_page_base_file(): void
    {
        // -----
        // First, load the main language file(s) for the current page . The 'english/lang.{page-name}.php'
        // file is always loaded, with its constant values possibly overwritten by a different page-specific
        // language file (e.g. 'spanish/lang.{page-name}.php').
        //
        // These definitions are added to the to-be-generated constants' list.
        //
        $current_page_base_file = '/lang.' . $this->current_page . '.php';
        $main_file = DIR_WS_LANGUAGES . $_SESSION['language'] . $current_page_base_file;
        $fallback_file = DIR_WS_LANGUAGES . $this->fallback . $current_page_base_file;
        $define_list = $this->load_defines_with_fallback($main_file, $fallback_file);
        // -----
        // Next, check each enabled zc_plugin to see if any page-specific language file
        // is present.
        //
        foreach ($this->plugin_list as $plugin) {
            $plugin_dir = $this->zc_plugins_dir . $plugin['unique_key'] . '/' . $plugin['version'] . '/catalog/includes/languages/';
            $main_file = $plugin_dir . $_SESSION['language'] . $current_page_base_file;
            $fallback_file = $plugin_dir . $this->fallback . $current_page_base_file;
            $define_list = array_merge($define_list, $this->load_defines_with_fallback($main_file, $fallback_file));
            $main_file = $plugin_dir . $_SESSION['language'] . '/default' . $current_page_base_file;
            $fallback_file = $plugin_dir . $this->fallback . '/default' . $current_page_base_file;
            $define_list = array_merge($define_list, $this->load_defines_with_fallback($main_file, $fallback_file));
        }
        // -----
        // Finally, if there is a template-override file **in the current session's language**,
        // load those definitions, adding to the to-be-generated constants' list.
        //
        // Any definitions found in this file overwrite all previously-loaded definitions for
        // the page-specific base language file.
        //
        $template_dir = '/' . $this->template_dir;
        $template_main_file = DIR_WS_LANGUAGES . $_SESSION['language'] . $template_dir . $current_page_base_file;
        $define_list = array_merge($define_list, $this->load_array_define_file($template_main_file));
        // -----
        // Make constants from the list of array-based language definitions for the
        // current page.
        //
        $this->make_constants($define_list);
    }
    /**
     * @since ZC v2.1.0
     */
    protected function load_current_page_extra_files_from_dir(string $directory): array
    {
        // -----
        // The specified directory is searched for 'lang.' files (alphabetically sorted) that
        // apply to the current page (i.e. $current_page_base) that have at least 1 character
        // difference with the 'base' file for the page.  For example, lang.account_information.php
        // but not lang.account.php for the 'account' page.
        //
        $files_regex = '~^lang.' . $this->current_page . '(.+)\.php$~i';
        $defines = [];
        $files = $this->file_system->list_files_from_directory_alpha_sorted($directory, $files_regex);
        foreach ($files as $file) {
            $defines = array_merge($defines, $this->load_array_define_file($directory . '/' . $file));
        }
        return $defines;
    }
    /**
     * @since ZC v1.5.8
     */
    protected function load_language_extra_definitions(): void
    {
        // -----
        // First, load the fallback (i.e. 'english') extra language definitions. If the current
        // session language is different than 'english', load that language's files; they'll
        // overwrite any like-named definitions in the 'english' fallback.
        //
        // Any definitions found here will overwrite any definitions in the 'main' language files.
        //
        $define_list = $this->load_arrays_from_directory(DIR_WS_LANGUAGES, $this->fallback, '/extra_definitions');
        if ($_SESSION['language'] !== $this->fallback) {
            $define_list_lang = $this->load_arrays_from_directory(DIR_WS_LANGUAGES, $_SESSION['language'], '/extra_definitions');
            $define_list = array_merge($define_list, $define_list_lang);
        }
        // -----
        // Next, load the fallback (i.e. 'english') extra language definitions from any enabled zc_plugins. If the current
        // session language is different than 'english', load that language's files too; they'll
        // overwrite any like-named definitions in the 'english' fallback.
        //
        // Any definitions found here will overwrite any non-plugin extra definitions as well as any definitions
        // in the 'main' language files.
        //
        $define_list_plugin = $this->plugin_load_arrays_from_directory($this->fallback, '/extra_definitions', 'catalog');
        if ($_SESSION['language'] !== $this->fallback) {
            $define_list_lang = $this->plugin_load_arrays_from_directory($_SESSION['language'], '/extra_definitions', 'catalog');
            $define_list_plugin = array_merge($define_list_plugin, $define_list_lang);
        }
        $define_list = array_merge($define_list, $define_list_plugin);
        // -----
        // Next, load the fallback (i.e. 'english') extra language definitions from any enabled zc_plugins' 'default' directory.
        // If the current session language is different than 'english', load that language's files too; they'll
        // overwrite any like-named definitions in the 'english' fallback.
        //
        // Any definitions found here will overwrite any non-'default' plugins' extra definitions, non-plugin extra definitions
        // as well as any definitions in the 'main' language files.
        //
        $define_list_plugin = $this->plugin_load_arrays_from_directory($this->fallback, '/extra_definitions/default', 'catalog');
        if ($_SESSION['language'] !== $this->fallback) {
            $define_list_lang = $this->plugin_load_arrays_from_directory($_SESSION['language'], '/extra_definitions/default', 'catalog');
            $define_list_plugin = array_merge($define_list_plugin, $define_list_lang);
        }
        $define_list = array_merge($define_list, $define_list_plugin);
        // -----
        // Finally, load any extra definitions in the current template's override directory, **for the current session language*.
        //
        // Any definitions found here overwrite **all** previous-found definitions.
        //
        $define_list_template = $this->load_arrays_from_directory(DIR_WS_LANGUAGES, $_SESSION['language'], '/extra_definitions/' . $this->template_dir);
        // -----
        // Add these extra definitions to the array of definitions to be created, if not further overridden
        // by any 'legacy' language files to be loaded.
        //
        $this->add_language_defines(array_merge($define_list, $define_list_template));
    }
    /**
     * @since ZC v1.5.8
     */
    protected function load_main_language_files(): void
    {
        // -----
        // First, load the main language file(s). The 'lang.english.php' file is always
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
        // Next, if there is a template-override file **for the current session's language**,
        // load those definitions, adding to the to-be-generated constants' list.
        //
        // Any definitions found in this file overwrite the 'base' main language files.
        //
        $template_main_file = DIR_WS_LANGUAGES . $this->template_dir . '/lang.' . $_SESSION['language'] . '.php';
        $define_list = $this->load_array_define_file($template_main_file);
        $this->add_language_defines($define_list);
        // -----
        // Finally, load the various 'other' language files that have definitions used
        // on multiple pages.
        //
        // Each of these files is first loaded from the 'fallback' (i.e. 'english') subdirectory,
        // followed by the current session language directory and finally (if present) in the current
        // language's template-override directory.
        //
        // Note: These files are not checked for presence in zc_plugins!
        //
        $extra_files = [FILENAME_EMAIL_EXTRAS, FILENAME_HEADER, FILENAME_BUTTON_NAMES, FILENAME_ICON_NAMES, FILENAME_OTHER_IMAGES_NAMES, FILENAME_CREDIT_CARDS, FILENAME_WHOS_ONLINE, FILENAME_META_TAGS];
        foreach ($extra_files as $file) {
            $file = basename($file, '.php') . '.php';
            $this->load_defines_from_dir_file_with_fallback(DIR_WS_LANGUAGES, $file);
            $define_list = $this->load_array_define_file(DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . $this->template_dir . '/lang.' . $file);
            $this->add_language_defines($define_list);
        }
    }
}