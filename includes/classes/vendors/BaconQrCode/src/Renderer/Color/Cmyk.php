<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Color;

use Bacon_Qr_Code\Exception;
final readonly class Cmyk implements Color_Interface
{
    /**
     * @param int $cyan the cyan amount, 0 to 100
     * @param int $magenta the magenta amount, 0 to 100
     * @param int $yellow the yellow amount, 0 to 100
     * @param int $black the black amount, 0 to 100
     */
    public function __construct(private int $cyan, private int $magenta, private int $yellow, private int $black)
    {
        if ($cyan < 0 || $cyan > 100) {
            throw new Exception\InvalidArgumentException('Cyan must be between 0 and 100');
        }
        if ($magenta < 0 || $magenta > 100) {
            throw new Exception\InvalidArgumentException('Magenta must be between 0 and 100');
        }
        if ($yellow < 0 || $yellow > 100) {
            throw new Exception\InvalidArgumentException('Yellow must be between 0 and 100');
        }
        if ($black < 0 || $black > 100) {
            throw new Exception\InvalidArgumentException('Black must be between 0 and 100');
        }
    }
    public function get_cyan(): int
    {
        return $this->cyan;
    }
    public function get_magenta(): int
    {
        return $this->magenta;
    }
    public function get_yellow(): int
    {
        return $this->yellow;
    }
    public function get_black(): int
    {
        return $this->black;
    }
    public function to_rgb(): Rgb
    {
        $k = $this->black / 100;
        $c = (-$k * $this->cyan + $k * 100 + $this->cyan) / 100;
        $m = (-$k * $this->magenta + $k * 100 + $this->magenta) / 100;
        $y = (-$k * $this->yellow + $k * 100 + $this->yellow) / 100;
        return new Rgb((int) (-$c * 255 + 255), (int) (-$m * 255 + 255), (int) (-$y * 255 + 255));
    }
    public function to_cmyk(): Cmyk
    {
        return $this;
    }
    public function to_gray(): Gray
    {
        return $this->to_rgb()->to_gray();
    }
}