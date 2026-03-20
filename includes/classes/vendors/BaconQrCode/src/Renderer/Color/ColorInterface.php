<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Color;

interface Color_Interface
{
    /**
     * Converts the color to RGB.
     */
    public function to_rgb(): Rgb;
    /**
     * Converts the color to CMYK.
     */
    public function to_cmyk(): Cmyk;
    /**
     * Converts the color to gray.
     */
    public function to_gray(): Gray;
}