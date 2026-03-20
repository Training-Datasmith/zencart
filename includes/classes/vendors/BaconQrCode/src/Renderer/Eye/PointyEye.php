<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Eye;

use Bacon_Qr_Code\Renderer\Path\Path;
/**
 * Renders the outer eye as solid with a curved corner and inner eye as a circle.
 */
final class Pointy_Eye implements Eye_Interface
{
    private static ?\Bacon_Qr_Code\Renderer\Eye\Pointy_Eye $instance = null;
    private function __construct()
    {
    }
    public static function instance(): self
    {
        return self::$instance ?: self::$instance = new self();
    }
    public function get_external_path(): Path
    {
        return (new Path())->move(-3.5, 3.5)->line(-3.5, 0)->elliptic_arc(3.5, 3.5, 0, false, true, 0, -3.5)->line(3.5, -3.5)->line(3.5, 3.5)->close()->move(2.5, 0)->elliptic_arc(2.5, 2.5, 0, false, true, 0, 2.5)->elliptic_arc(2.5, 2.5, 0, false, true, -2.5, 0)->elliptic_arc(2.5, 2.5, 0, false, true, 0, -2.5)->elliptic_arc(2.5, 2.5, 0, false, true, 2.5, 0)->close();
    }
    public function get_internal_path(): Path
    {
        return (new Path())->move(1.5, 0)->elliptic_arc(1.5, 1.5, 0.0, false, true, 0.0, 1.5)->elliptic_arc(1.5, 1.5, 0.0, false, true, -1.5, 0.0)->elliptic_arc(1.5, 1.5, 0.0, false, true, 0.0, -1.5)->elliptic_arc(1.5, 1.5, 0.0, false, true, 1.5, 0.0)->close();
    }
}