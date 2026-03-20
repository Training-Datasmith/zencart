<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Renderer_Style;

use Dasp_Ri_D\Enum\Abstract_Enum;
/**
 * @method static self VERTICAL()
 * @method static self HORIZONTAL()
 * @method static self DIAGONAL()
 * @method static self INVERSE_DIAGONAL()
 * @method static self RADIAL()
 */
final class Gradient_Type extends Abstract_Enum
{
    protected const VERTICAL = null;
    protected const HORIZONTAL = null;
    protected const DIAGONAL = null;
    protected const INVERSE_DIAGONAL = null;
    protected const RADIAL = null;
}