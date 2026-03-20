<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Renderer_Style;

use Bacon_Qr_Code\Exception\RuntimeException;
use Bacon_Qr_Code\Renderer\Color\Color_Interface;
final class Eye_Fill
{
    private static ?Eye_Fill $inherit = null;
    public function __construct(private readonly ?Color_Interface $external_color, private readonly ?Color_Interface $internal_color)
    {
    }
    public static function uniform(Color_Interface $color): self
    {
        return new self($color, $color);
    }
    public static function inherit(): self
    {
        return self::$inherit ?: self::$inherit = new self(null, null);
    }
    public function inherits_both_colors(): bool
    {
        return null === $this->external_color && null === $this->internal_color;
    }
    public function inherits_external_color(): bool
    {
        return null === $this->external_color;
    }
    public function inherits_internal_color(): bool
    {
        return null === $this->internal_color;
    }
    public function get_external_color(): Color_Interface
    {
        if (null === $this->external_color) {
            throw new RuntimeException('External eye color inherits foreground color');
        }
        return $this->external_color;
    }
    public function get_internal_color(): Color_Interface
    {
        if (null === $this->internal_color) {
            throw new RuntimeException('Internal eye color inherits foreground color');
        }
        return $this->internal_color;
    }
}