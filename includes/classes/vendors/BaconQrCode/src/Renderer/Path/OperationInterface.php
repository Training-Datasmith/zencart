<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Path;

interface Operation_Interface
{
    /**
     * Translates the operation's coordinates.
     */
    public function translate(float $x, float $y): self;
    /**
     * Rotates the operation's coordinates.
     */
    public function rotate(int $degrees): self;
}