<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Eye;

use Bacon_Qr_Code\Renderer\Path\Path;
/**
 * Combines the style of two different eyes.
 */
final readonly class Composite_Eye implements Eye_Interface
{
    public function __construct(private Eye_Interface $external_eye, private Eye_Interface $internal_eye)
    {
    }
    public function get_external_path(): Path
    {
        return $this->external_eye->get_external_path();
    }
    public function get_internal_path(): Path
    {
        return $this->internal_eye->get_internal_path();
    }
}