<?php

declare(strict_types=1);
/**
 * DownloadStatus — backed string enum for the DOWNLOAD_ENABLED configuration constant.
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license   http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 *
 * Replaces stringly-typed comparisons like:
 *   if (DOWNLOAD_ENABLED == 'true') { ... }
 *
 * Usage:
 *   $status = DownloadStatus::from(DOWNLOAD_ENABLED);
 *   if ($status === DownloadStatus::Enabled) { ... }
 *
 * @since ZC v2.2.0
 */
enum DownloadStatus: string
{
    /** Downloads are enabled for the storefront. */
    case Enabled = 'true';

    /** Downloads are disabled for the storefront. */
    case Disabled = 'false';

    /**
     * Returns whether downloads are enabled.
     *
     * @return bool  True if this status is Enabled.
     */
    public function isEnabled(): bool
    {
        return $this === self::Enabled;
    }
}
