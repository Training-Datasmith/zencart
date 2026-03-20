<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Eye;

use Bacon_Qr_Code\Renderer\Path\Path;
/**
 * Renders the eyes in their default square shape.
 */
final class Square_Eye implements Eye_Interface
{
    private static ?Square_Eye $instance = null;
    private function __construct()
    {
    }
    public static function instance(): self
    {
        return self::$instance ?: self::$instance = new self();
    }
    public function get_external_path(): Path
    {
        return (new Path())->move(-3.5, -3.5)->line(3.5, -3.5)->line(3.5, 3.5)->line(-3.5, 3.5)->close()->move(-2.5, -2.5)->line(-2.5, 2.5)->line(2.5, 2.5)->line(2.5, -2.5)->close();
    }
    public function get_internal_path(): Path
    {
        return (new Path())->move(-1.5, -1.5)->line(1.5, -1.5)->line(1.5, 1.5)->line(-1.5, 1.5)->close();
    }
}