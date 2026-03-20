<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Eye;

use Bacon_Qr_Code\Encoder\Byte_Matrix;
use Bacon_Qr_Code\Renderer\Module\Module_Interface;
use Bacon_Qr_Code\Renderer\Path\Path;
/**
 * Renders an eye based on a module renderer.
 */
final readonly class Module_Eye implements Eye_Interface
{
    public function __construct(private Module_Interface $module)
    {
    }
    public function get_external_path(): Path
    {
        $matrix = new Byte_Matrix(7, 7);
        for ($x = 0; $x < 7; ++$x) {
            $matrix->set($x, 0, 1);
            $matrix->set($x, 6, 1);
        }
        for ($y = 1; $y < 6; ++$y) {
            $matrix->set(0, $y, 1);
            $matrix->set(6, $y, 1);
        }
        return $this->module->create_path($matrix)->translate(-3.5, -3.5);
    }
    public function get_internal_path(): Path
    {
        $matrix = new Byte_Matrix(3, 3);
        for ($x = 0; $x < 3; ++$x) {
            for ($y = 0; $y < 3; ++$y) {
                $matrix->set($x, $y, 1);
            }
        }
        return $this->module->create_path($matrix)->translate(-1.5, -1.5);
    }
}