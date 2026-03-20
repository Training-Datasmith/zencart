<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Renderer_Style;

use Bacon_Qr_Code\Renderer\Eye\Eye_Interface;
use Bacon_Qr_Code\Renderer\Eye\Module_Eye;
use Bacon_Qr_Code\Renderer\Module\Module_Interface;
use Bacon_Qr_Code\Renderer\Module\Square_Module;
final class Renderer_Style
{
    private Module_Interface $module;
    private Eye_Interface|null $eye;
    private Fill $fill;
    public function __construct(private int $size, private int $margin = 4, ?Module_Interface $module = null, ?Eye_Interface $eye = null, ?Fill $fill = null)
    {
        $this->module = $module ?: Square_Module::instance();
        $this->eye = $eye ?: new Module_Eye($this->module);
        $this->fill = $fill ?: Fill::default();
    }
    public function with_size(int $size): self
    {
        $style = clone $this;
        $style->size = $size;
        return $style;
    }
    public function with_margin(int $margin): self
    {
        $style = clone $this;
        $style->margin = $margin;
        return $style;
    }
    public function get_size(): int
    {
        return $this->size;
    }
    public function get_margin(): int
    {
        return $this->margin;
    }
    public function get_module(): Module_Interface
    {
        return $this->module;
    }
    public function get_eye(): Eye_Interface
    {
        return $this->eye;
    }
    public function get_fill(): Fill
    {
        return $this->fill;
    }
}