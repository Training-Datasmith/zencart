<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Path;

final readonly class Curve implements Operation_Interface
{
    public function __construct(private float $x1, private float $y1, private float $x2, private float $y2, private float $x3, private float $y3)
    {
    }
    public function get_x1(): float
    {
        return $this->x1;
    }
    public function get_y1(): float
    {
        return $this->y1;
    }
    public function get_x2(): float
    {
        return $this->x2;
    }
    public function get_y2(): float
    {
        return $this->y2;
    }
    public function get_x3(): float
    {
        return $this->x3;
    }
    public function get_y3(): float
    {
        return $this->y3;
    }
    /**
     * @return self
     */
    public function translate(float $x, float $y): Operation_Interface
    {
        return new self($this->x1 + $x, $this->y1 + $y, $this->x2 + $x, $this->y2 + $y, $this->x3 + $x, $this->y3 + $y);
    }
    /**
     * @return self
     */
    public function rotate(int $degrees): Operation_Interface
    {
        $radians = deg2rad($degrees);
        $sin = sin($radians);
        $cos = cos($radians);
        $x1r = $this->x1 * $cos - $this->y1 * $sin;
        $y1r = $this->x1 * $sin + $this->y1 * $cos;
        $x2r = $this->x2 * $cos - $this->y2 * $sin;
        $y2r = $this->x2 * $sin + $this->y2 * $cos;
        $x3r = $this->x3 * $cos - $this->y3 * $sin;
        $y3r = $this->x3 * $sin + $this->y3 * $cos;
        return new self($x1r, $y1r, $x2r, $y2r, $x3r, $y3r);
    }
}