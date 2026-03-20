<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Color;

use Bacon_Qr_Code\Exception;
final readonly class Gray implements Color_Interface
{
    /**
     * @param int $gray the gray value between 0 (black) and 100 (white)
     */
    public function __construct(private int $gray)
    {
        if ($gray < 0 || $gray > 100) {
            throw new Exception\InvalidArgumentException('Gray must be between 0 and 100');
        }
    }
    public function get_gray(): int
    {
        return $this->gray;
    }
    public function to_rgb(): Rgb
    {
        return new Rgb((int) ($this->gray * 2.55), (int) ($this->gray * 2.55), (int) ($this->gray * 2.55));
    }
    public function to_cmyk(): Cmyk
    {
        return new Cmyk(0, 0, 0, 100 - $this->gray);
    }
    public function to_gray(): Gray
    {
        return $this;
    }
}