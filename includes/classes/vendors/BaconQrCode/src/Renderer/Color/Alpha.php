<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Color;

use Bacon_Qr_Code\Exception;
final readonly class Alpha implements Color_Interface
{
    /**
     * @param int $alpha the alpha value, 0 to 100
     */
    public function __construct(private int $alpha, private Color_Interface $base_color)
    {
        if ($alpha < 0 || $alpha > 100) {
            throw new Exception\InvalidArgumentException('Alpha must be between 0 and 100');
        }
    }
    public function get_alpha(): int
    {
        return $this->alpha;
    }
    public function get_base_color(): Color_Interface
    {
        return $this->base_color;
    }
    public function to_rgb(): Rgb
    {
        return $this->base_color->to_rgb();
    }
    public function to_cmyk(): Cmyk
    {
        return $this->base_color->to_cmyk();
    }
    public function to_gray(): Gray
    {
        return $this->base_color->to_gray();
    }
}