<?php

declare(strict_types=1);
/**
 * PCI Patch for v1.3.x -- to aid in avoiding false-positives thrown by PCI scans
 *
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Scott Wilson 2025 May 24 Modified in v2.2.0 $
 */
/**
 *
 * Please Note : This file should be placed in includes/extra_configures and will automatically load.
 *
 */

if (isset($_GET['keyword']) && is_array($_GET['keyword'])) {
    $_GET['keyword'] = '';
}
if (isset($_GET['keyword']) && $_GET['keyword'] != '') {
    $count =  substr_count((string) $_GET['keyword'], '"');
    if ($count == 1) {
        if (str_starts_with(stripslashes(trim((string) $_GET['keyword'])), '"')) {
            $_GET['keyword'] .= '"';
        }
    }
    $_GET['keyword'] = stripslashes((string) $_GET['keyword']);
}
if (isset($_GET['sort']) && strlen((string) $_GET['sort']) > 3) {
    $_GET['sort'] = substr((string) $_GET['sort'], 0, 3);
}
