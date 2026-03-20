<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Eye;

use Bacon_Qr_Code\Renderer\Path\Path;
/**
 * Interface for describing the look of an eye.
 */
interface Eye_Interface
{
    /**
     * Returns the path of the external eye element.
     *
     * The path origin point (0, 0) must be anchored at the middle of the path.
     */
    public function get_external_path(): Path;
    /**
     * Returns the path of the internal eye element.
     *
     * The path origin point (0, 0) must be anchored at the middle of the path.
     */
    public function get_internal_path(): Path;
}