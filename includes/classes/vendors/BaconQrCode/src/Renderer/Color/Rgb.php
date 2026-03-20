<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Color;

use Bacon_Qr_Code\Exception;
final readonly class Rgb implements Color_Interface
{
    /**
     * @param int $red the red amount of the color, 0 to 255
     * @param int $green the green amount of the color, 0 to 255
     * @param int $blue the blue amount of the color, 0 to 255
     */
    public function __construct(private int $red, private int $green, private int $blue)
    {
        if ($red < 0 || $red > 255) {
            throw new Exception\InvalidArgumentException('Red must be between 0 and 255');
        }
        if ($green < 0 || $green > 255) {
            throw new Exception\InvalidArgumentException('Green must be between 0 and 255');
        }
        if ($blue < 0 || $blue > 255) {
            throw new Exception\InvalidArgumentException('Blue must be between 0 and 255');
        }
    }
    public function get_red(): int
    {
        return $this->red;
    }
    public function get_green(): int
    {
        return $this->green;
    }
    public function get_blue(): int
    {
        return $this->blue;
    }
    public function to_rgb(): Rgb
    {
        return $this;
    }
    public function to_cmyk(): Cmyk
    {
        $c = 1 - $this->red / 255;
        $m = 1 - $this->green / 255;
        $y = 1 - $this->blue / 255;
        $k = min($c, $m, $y);
        if ($k === 0) {
            return new Cmyk(0, 0, 0, 0);
        }
        return new Cmyk((int) (100 * ($c - $k) / (1 - $k)), (int) (100 * ($m - $k) / (1 - $k)), (int) (100 * ($y - $k) / (1 - $k)), (int) (100 * $k));
    }
    public function to_gray(): Gray
    {
        return new Gray((int) (($this->red * 0.21 + $this->green * 0.71 + $this->blue * 0.07000000000000001) / 2.55));
    }
}