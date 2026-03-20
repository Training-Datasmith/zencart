<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Module;

use Bacon_Qr_Code\Encoder\Byte_Matrix;
use Bacon_Qr_Code\Exception\InvalidArgumentException;
use Bacon_Qr_Code\Renderer\Module\Edge_Iterator\Edge_Iterator;
use Bacon_Qr_Code\Renderer\Path\Path;
/**
 * Rounds the corners of module groups.
 */
final class Roundness_Module implements Module_Interface
{
    public const STRONG = 1;
    public const MEDIUM = 0.5;
    public const SOFT = 0.25;
    public function __construct(private float $intensity)
    {
        if ($intensity <= 0 || $intensity > 1) {
            throw new InvalidArgumentException('Intensity must between 0 (exclusive) and 1 (inclusive)');
        }
        $this->intensity = $intensity / 2;
    }
    public function create_path(Byte_Matrix $matrix): Path
    {
        $path = new Path();
        foreach (new Edge_Iterator($matrix) as $edge) {
            $points = $edge->get_simplified_points();
            $length = count($points);
            $current_point = $points[0];
            $next_point = $points[1];
            $horizontal = $current_point[1] === $next_point[1];
            if ($horizontal) {
                $right = $next_point[0] > $current_point[0];
                $path = $path->move($current_point[0] + ($right ? $this->intensity : -$this->intensity), $current_point[1]);
            } else {
                $up = $next_point[0] < $current_point[0];
                $path = $path->move($current_point[0], $current_point[1] + ($up ? -$this->intensity : $this->intensity));
            }
            for ($i = 1; $i <= $length; ++$i) {
                if ($i === $length) {
                    $previous_point = $points[$length - 1];
                    $current_point = $points[0];
                    $next_point = $points[1];
                } else {
                    $previous_point = $points[(0 === $i ? $length : $i) - 1];
                    $current_point = $points[$i];
                    $next_point = $points[($length - 1 === $i ? -1 : $i) + 1];
                }
                $horizontal = $previous_point[1] === $current_point[1];
                if ($horizontal) {
                    $right = $previous_point[0] < $current_point[0];
                    $up = $next_point[1] < $current_point[1];
                    $sweep = ($up xor $right);
                    if ($this->intensity < 0.5 || $right && $previous_point[0] !== $current_point[0] - 1 || !$right && $previous_point[0] - 1 !== $current_point[0]) {
                        $path = $path->line($current_point[0] + ($right ? -$this->intensity : $this->intensity), $current_point[1]);
                    }
                    $path = $path->elliptic_arc($this->intensity, $this->intensity, 0, false, $sweep, $current_point[0], $current_point[1] + ($up ? -$this->intensity : $this->intensity));
                } else {
                    $up = $previous_point[1] > $current_point[1];
                    $right = $next_point[0] > $current_point[0];
                    $sweep = !($up xor $right);
                    if ($this->intensity < 0.5 || $up && $previous_point[1] !== $current_point[1] + 1 || !$up && $previous_point[0] + 1 !== $current_point[0]) {
                        $path = $path->line($current_point[0], $current_point[1] + ($up ? $this->intensity : -$this->intensity));
                    }
                    $path = $path->elliptic_arc($this->intensity, $this->intensity, 0, false, $sweep, $current_point[0] + ($right ? $this->intensity : -$this->intensity), $current_point[1]);
                }
            }
            $path = $path->close();
        }
        return $path;
    }
}