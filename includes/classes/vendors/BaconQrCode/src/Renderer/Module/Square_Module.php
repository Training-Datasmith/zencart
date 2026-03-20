<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Module;

use Bacon_Qr_Code\Encoder\Byte_Matrix;
use Bacon_Qr_Code\Renderer\Module\Edge_Iterator\Edge_Iterator;
use Bacon_Qr_Code\Renderer\Path\Path;
/**
 * Groups modules together to a single path.
 */
final class Square_Module implements Module_Interface
{
    private static ?Square_Module $instance = null;
    private function __construct()
    {
    }
    public static function instance(): self
    {
        return self::$instance ?: self::$instance = new self();
    }
    public function create_path(Byte_Matrix $matrix): Path
    {
        $path = new Path();
        foreach (new Edge_Iterator($matrix) as $edge) {
            $points = $edge->get_simplified_points();
            $length = count($points);
            $path = $path->move($points[0][0], $points[0][1]);
            for ($i = 1; $i < $length; ++$i) {
                $path = $path->line($points[$i][0], $points[$i][1]);
            }
            $path = $path->close();
        }
        return $path;
    }
}