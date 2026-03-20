<?php

declare (strict_types=1);
/**
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
namespace Zencart\Language_Loader;

use Zencart\File_System\File_System;
/**
 * @since ZC v1.5.8
 */
class Base_Language_Loader
{
    protected \Zencart\File_System\File_System $file_system;
    protected array $language_defines = [];
    protected string $zc_plugins_dir;
    public function __construct(protected array $plugin_list, public string $current_page, protected string $template_dir, protected string $fallback = 'english')
    {
        $this->file_system = new File_System();
        $this->zc_plugins_dir = DIR_FS_CATALOG . 'zc_plugins/';
    }
    /**
     * @since ZC v2.2.0
     */
    public function get_template_dir(): string
    {
        return $this->template_dir;
    }
    /**
     * @since ZC v2.2.0
     */
    public function get_fallback(): string
    {
        return $this->fallback;
    }
}