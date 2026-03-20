<?php

declare (strict_types=1);
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @author ZCAdditions.com, ZCA Responsive Template Default
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */
/**
 * @since ZC v1.5.5
 */
function layout_types(): array
{
    return ['default', 'mobile', 'tablet', 'full'];
}
/**
 * @since ZC v1.5.5
 */
function init_layout_type()
{
    // Safety check.
    if (!class_exists('MobileDetect')) {
        return 'default';
    }
    $detect = new Detection\Mobile_Detect();
    $is_mobile = $detect->is_mobile();
    $is_tablet = $detect->is_tablet();
    $layout_types = layout_types();
    if (isset($_GET['layoutType'])) {
        $layout_type = $_GET['layoutType'];
    } else if (empty($_SESSION['layoutType'])) {
        $layout_type = $is_mobile ? $is_tablet ? 'tablet' : 'mobile' : 'default';
    } else {
        $layout_type = $_SESSION['layoutType'];
    }
    if (!in_array($layout_type, $layout_types)) {
        $layout_type = 'default';
    }
    $_SESSION['layoutType'] = $layout_type;
    return $layout_type;
}
$layout_type = init_layout_type();