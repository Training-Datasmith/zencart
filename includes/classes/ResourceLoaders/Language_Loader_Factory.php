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
class Language_Loader_Factory
{
    /**
     * @since ZC v1.5.8
     */
    public function make(string $context, array $installed_plugins, string $current_page, string $template_directory, string $fallback = 'english'): \Zencart\Language_Loader\Language_Loader
    {
        $arrays_loader = $this->make_arrays_loader($context, $installed_plugins, $current_page, $template_directory, $fallback);
        $files_loader = $this->make_files_loader($context, $installed_plugins, $current_page, $template_directory, $fallback);
        return new Language_Loader($arrays_loader, $files_loader);
    }
    /**
     * @since ZC v1.5.8
     */
    protected function make_arrays_loader(string $context, array $installed_plugins, string $current_page, string $template_directory, string $fallback)
    {
        $class_name = 'Zencart\LanguageLoader\\' . ucfirst(strtolower($context)) . 'ArraysLanguageLoader';
        return new $class_name($installed_plugins, $current_page, $template_directory, $fallback);
    }
    /**
     * @since ZC v1.5.8
     */
    protected function make_files_loader(string $context, array $installed_plugins, string $current_page, string $template_directory, string $fallback)
    {
        $class_name = 'Zencart\LanguageLoader\\' . ucfirst(strtolower($context)) . 'FilesLanguageLoader';
        return new $class_name($installed_plugins, $current_page, $template_directory, $fallback);
    }
}