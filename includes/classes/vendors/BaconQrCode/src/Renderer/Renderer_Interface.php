<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer;

use Bacon_Qr_Code\Encoder\Qr_Code;
interface Renderer_Interface
{
    public function render(Qr_Code $qr_code): string;
}