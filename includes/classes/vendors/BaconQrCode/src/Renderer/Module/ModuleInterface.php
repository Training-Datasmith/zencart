<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Module;

use Bacon_Qr_Code\Encoder\Byte_Matrix;
use Bacon_Qr_Code\Renderer\Path\Path;
/**
 * Interface describing how modules should be rendered.
 *
 * A module always receives a byte matrix (with values either being 1 or 0). It returns a path, where the origin
 * coordinate (0, 0) equals the top left corner of the first matrix value.
 */
interface Module_Interface
{
    public function create_path(Byte_Matrix $matrix): Path;
}