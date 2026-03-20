<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Renderer_Style;

use Bacon_Qr_Code\Renderer\Color\Color_Interface;
final readonly class Gradient
{
    public function __construct(private Color_Interface $start_color, private Color_Interface $end_color, private Gradient_Type $type)
    {
    }
    public function get_start_color(): Color_Interface
    {
        return $this->start_color;
    }
    public function get_end_color(): Color_Interface
    {
        return $this->end_color;
    }
    public function get_type(): Gradient_Type
    {
        return $this->type;
    }
}