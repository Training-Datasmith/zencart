<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Path;

final readonly class Move implements Operation_Interface
{
    public function __construct(private float $x, private float $y)
    {
    }
    public function get_x(): float
    {
        return $this->x;
    }
    public function get_y(): float
    {
        return $this->y;
    }
    /**
     * @return self
     */
    public function translate(float $x, float $y): Operation_Interface
    {
        return new self($this->x + $x, $this->y + $y);
    }
    /**
     * @return self
     */
    public function rotate(int $degrees): Operation_Interface
    {
        $radians = deg2rad($degrees);
        $sin = sin($radians);
        $cos = cos($radians);
        $xr = $this->x * $cos - $this->y * $sin;
        $yr = $this->x * $sin + $this->y * $cos;
        return new self($xr, $yr);
    }
}