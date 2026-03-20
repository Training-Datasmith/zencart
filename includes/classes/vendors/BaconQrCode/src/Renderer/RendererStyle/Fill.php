<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Renderer_Style;

use Bacon_Qr_Code\Exception\RuntimeException;
use Bacon_Qr_Code\Renderer\Color\Color_Interface;
use Bacon_Qr_Code\Renderer\Color\Gray;
final class Fill
{
    private static ?Fill $default = null;
    private function __construct(private readonly Color_Interface $background_color, private readonly ?Color_Interface $foreground_color, private readonly ?Gradient $foreground_gradient, private readonly Eye_Fill $top_left_eye_fill, private readonly Eye_Fill $top_right_eye_fill, private readonly Eye_Fill $bottom_left_eye_fill)
    {
    }
    public static function default(): self
    {
        return self::$default ?: self::$default = self::uniform_color(new Gray(100), new Gray(0));
    }
    public static function with_foreground_color(Color_Interface $background_color, Color_Interface $foreground_color, Eye_Fill $top_left_eye_fill, Eye_Fill $top_right_eye_fill, Eye_Fill $bottom_left_eye_fill): self
    {
        return new self($background_color, $foreground_color, null, $top_left_eye_fill, $top_right_eye_fill, $bottom_left_eye_fill);
    }
    public static function with_foreground_gradient(Color_Interface $background_color, Gradient $foreground_gradient, Eye_Fill $top_left_eye_fill, Eye_Fill $top_right_eye_fill, Eye_Fill $bottom_left_eye_fill): self
    {
        return new self($background_color, null, $foreground_gradient, $top_left_eye_fill, $top_right_eye_fill, $bottom_left_eye_fill);
    }
    public static function uniform_color(Color_Interface $background_color, Color_Interface $foreground_color): self
    {
        return new self($background_color, $foreground_color, null, Eye_Fill::inherit(), Eye_Fill::inherit(), Eye_Fill::inherit());
    }
    public static function uniform_gradient(Color_Interface $background_color, Gradient $foreground_gradient): self
    {
        return new self($background_color, null, $foreground_gradient, Eye_Fill::inherit(), Eye_Fill::inherit(), Eye_Fill::inherit());
    }
    public function has_gradient_fill(): bool
    {
        return null !== $this->foreground_gradient;
    }
    public function get_background_color(): Color_Interface
    {
        return $this->background_color;
    }
    public function get_foreground_color(): Color_Interface
    {
        if (null === $this->foreground_color) {
            throw new RuntimeException('Fill uses a gradient, thus no foreground color is available');
        }
        return $this->foreground_color;
    }
    public function get_foreground_gradient(): Gradient
    {
        if (null === $this->foreground_gradient) {
            throw new RuntimeException('Fill uses a single color, thus no foreground gradient is available');
        }
        return $this->foreground_gradient;
    }
    public function get_top_left_eye_fill(): Eye_Fill
    {
        return $this->top_left_eye_fill;
    }
    public function get_top_right_eye_fill(): Eye_Fill
    {
        return $this->top_right_eye_fill;
    }
    public function get_bottom_left_eye_fill(): Eye_Fill
    {
        return $this->bottom_left_eye_fill;
    }
}