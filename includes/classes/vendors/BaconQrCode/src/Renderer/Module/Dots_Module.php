<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Module;

use Bacon_Qr_Code\Encoder\Byte_Matrix;
use Bacon_Qr_Code\Exception\InvalidArgumentException;
use Bacon_Qr_Code\Renderer\Path\Path;
/**
 * Renders individual modules as dots.
 */
final readonly class Dots_Module implements Module_Interface
{
    public const LARGE = 1;
    public const MEDIUM = 0.8;
    public const SMALL = 0.6;
    public function __construct(private float $size)
    {
        if ($size <= 0 || $size > 1) {
            throw new InvalidArgumentException('Size must between 0 (exclusive) and 1 (inclusive)');
        }
    }
    public function create_path(Byte_Matrix $matrix): Path
    {
        $width = $matrix->get_width();
        $height = $matrix->get_height();
        $path = new Path();
        $half_size = $this->size / 2;
        $margin = (1 - $this->size) / 2;
        for ($y = 0; $y < $height; ++$y) {
            for ($x = 0; $x < $width; ++$x) {
                if (!$matrix->get($x, $y)) {
                    continue;
                }
                $path_x = $x + $margin;
                $path_y = $y + $margin;
                $path = $path->move($path_x + $this->size, $path_y + $half_size)->elliptic_arc($half_size, $half_size, 0, false, true, $path_x + $half_size, $path_y + $this->size)->elliptic_arc($half_size, $half_size, 0, false, true, $path_x, $path_y + $half_size)->elliptic_arc($half_size, $half_size, 0, false, true, $path_x + $half_size, $path_y)->elliptic_arc($half_size, $half_size, 0, false, true, $path_x + $this->size, $path_y + $half_size)->close();
            }
        }
        return $path;
    }
}