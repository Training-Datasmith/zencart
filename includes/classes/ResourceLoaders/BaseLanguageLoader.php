<?php

declare(strict_types=1);
/**
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */

namespace Zencart\LanguageLoader;

use Zencart\FileSystem\FileSystem;

/**
 * @since ZC v1.5.8
 */
class BaseLanguageLoader
{
    protected \Zencart\FileSystem\FileSystem $fileSystem;
    protected array $languageDefines = [];
    protected string $zcPluginsDir;

    public function __construct(protected array $pluginList, public string $currentPage, protected string $templateDir, protected string $fallback = 'english')
    {
        $this->fileSystem = new FileSystem();
        $this->zcPluginsDir = DIR_FS_CATALOG . 'zc_plugins/';
    }

    /**
     * @since ZC v2.2.0
     */
    public function getTemplateDir(): string
    {
        return $this->templateDir;
    }

    /**
     * @since ZC v2.2.0
     */
    public function getFallback(): string
    {
        return $this->fallback;
    }
}
