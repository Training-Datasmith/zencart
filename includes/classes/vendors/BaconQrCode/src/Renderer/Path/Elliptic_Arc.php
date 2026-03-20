<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Path;

final readonly class Elliptic_Arc implements Operation_Interface
{
    private const ZERO_TOLERANCE = 1.0E-5;
    private float $x_radius;
    private float $y_radius;
    private float $x_axis_angle;
    public function __construct(float $x_radius, float $y_radius, float $x_axis_angle, private bool $large_arc, private bool $sweep, private float $x, private float $y)
    {
        $this->x_radius = abs($x_radius);
        $this->y_radius = abs($y_radius);
        $this->x_axis_angle = $x_axis_angle % 360;
    }
    public function get_x_radius(): float
    {
        return $this->x_radius;
    }
    public function get_y_radius(): float
    {
        return $this->y_radius;
    }
    public function get_x_axis_angle(): float
    {
        return $this->x_axis_angle;
    }
    public function is_large_arc(): bool
    {
        return $this->large_arc;
    }
    public function is_sweep(): bool
    {
        return $this->sweep;
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
        return new self($this->x_radius, $this->y_radius, $this->x_axis_angle, $this->large_arc, $this->sweep, $this->x + $x, $this->y + $y);
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
        return new self($this->x_radius, $this->y_radius, $this->x_axis_angle, $this->large_arc, $this->sweep, $xr, $yr);
    }
    /**
     * Converts the elliptic arc to multiple curves.
     *
     * Since not all image back ends support elliptic arcs, this method allows to convert the arc into multiple curves
     * resembling the same result.
     *
     * @see https://mortoray.com/2017/02/16/rendering-an-svg-elliptical-arc-as-bezier-curves/
     * @return array<Curve|Line>
     */
    public function to_curves(float $from_x, float $from_y): array
    {
        if (sqrt(($from_x - $this->x) ** 2 + ($from_y - $this->y) ** 2) < self::ZERO_TOLERANCE) {
            return [];
        }
        if ($this->x_radius < self::ZERO_TOLERANCE || $this->y_radius < self::ZERO_TOLERANCE) {
            return [new Line($this->x, $this->y)];
        }
        return $this->create_curves($from_x, $from_y);
    }
    /**
     * @return Curve[]
     */
    private function create_curves(float $from_x, float $from_y): array
    {
        $x_angle = deg2rad($this->x_axis_angle);
        [$center_x, $center_y, $radius_x, $radius_y, $start_angle, $delta_angle] = $this->calculate_center_point_parameters($from_x, $from_y, $x_angle);
        $s = $start_angle;
        $e = $s + $delta_angle;
        $sign = $e < $s ? -1 : 1;
        $remain = abs($e - $s);
        $p1 = self::point($center_x, $center_y, $radius_x, $radius_y, $x_angle, $s);
        $curves = [];
        while ($remain > self::ZERO_TOLERANCE) {
            $step = min($remain, pi() / 2);
            $sign_step = $step * $sign;
            $p2 = self::point($center_x, $center_y, $radius_x, $radius_y, $x_angle, $s + $sign_step);
            $alpha_t = tan($sign_step / 2);
            $alpha = sin($sign_step) * (sqrt(4 + 3 * $alpha_t ** 2) - 1) / 3;
            $d1 = self::derivative($radius_x, $radius_y, $x_angle, $s);
            $d2 = self::derivative($radius_x, $radius_y, $x_angle, $s + $sign_step);
            $curves[] = new Curve($p1[0] + $alpha * $d1[0], $p1[1] + $alpha * $d1[1], $p2[0] - $alpha * $d2[0], $p2[1] - $alpha * $d2[1], $p2[0], $p2[1]);
            $s += $sign_step;
            $remain -= $step;
            $p1 = $p2;
        }
        return $curves;
    }
    /**
     * @return float[]
     */
    private function calculate_center_point_parameters(float $from_x, float $from_y, float $x_angle): array
    {
        $r_x = $this->x_radius;
        $r_y = $this->y_radius;
        // F.6.5.1
        $dx2 = ($from_x - $this->x) / 2;
        $dy2 = ($from_y - $this->y) / 2;
        $x1p = cos($x_angle) * $dx2 + sin($x_angle) * $dy2;
        $y1p = -sin($x_angle) * $dx2 + cos($x_angle) * $dy2;
        // F.6.5.2
        $rxs = $r_x ** 2;
        $rys = $r_y ** 2;
        $x1ps = $x1p ** 2;
        $y1ps = $y1p ** 2;
        $cr = $x1ps / $rxs + $y1ps / $rys;
        if ($cr > 1) {
            $s = sqrt($cr);
            $r_x *= $s;
            $r_y *= $s;
            $rxs = $r_x ** 2;
            $rys = $r_y ** 2;
        }
        $dq = $rxs * $y1ps + $rys * $x1ps;
        $pq = ($rxs * $rys - $dq) / $dq;
        $q = sqrt(max(0, $pq));
        if ($this->large_arc === $this->sweep) {
            $q = -$q;
        }
        $cxp = $q * $r_x * $y1p / $r_y;
        $cyp = -$q * $r_y * $x1p / $r_x;
        // F.6.5.3
        $cx = cos($x_angle) * $cxp - sin($x_angle) * $cyp + ($from_x + $this->x) / 2;
        $cy = sin($x_angle) * $cxp + cos($x_angle) * $cyp + ($from_y + $this->y) / 2;
        // F.6.5.5
        $theta = self::angle(1, 0, ($x1p - $cxp) / $r_x, ($y1p - $cyp) / $r_y);
        // F.6.5.6
        $delta = self::angle(($x1p - $cxp) / $r_x, ($y1p - $cyp) / $r_y, (-$x1p - $cxp) / $r_x, (-$y1p - $cyp) / $r_y);
        $delta = fmod($delta, pi() * 2);
        if (!$this->sweep) {
            $delta -= 2 * pi();
        }
        return [$cx, $cy, $r_x, $r_y, $theta, $delta];
    }
    private static function angle(float $ux, float $uy, float $vx, float $vy): float
    {
        // F.6.5.4
        $dot = $ux * $vx + $uy * $vy;
        $length = sqrt($ux ** 2 + $uy ** 2) * sqrt($vx ** 2 + $vy ** 2);
        $angle = acos(min(1, max(-1, $dot / $length)));
        if ($ux * $vy - $uy * $vx < 0) {
            return -$angle;
        }
        return $angle;
    }
    /**
     * @return float[]
     */
    private static function point(float $center_x, float $center_y, float $radius_x, float $radius_y, float $x_angle, float $angle): array
    {
        return [$center_x + $radius_x * cos($x_angle) * cos($angle) - $radius_y * sin($x_angle) * sin($angle), $center_y + $radius_x * sin($x_angle) * cos($angle) + $radius_y * cos($x_angle) * sin($angle)];
    }
    /**
     * @return float[]
     */
    private static function derivative(float $radius_x, float $radius_y, float $x_angle, float $angle): array
    {
        return [-$radius_x * cos($x_angle) * sin($angle) - $radius_y * sin($x_angle) * cos($angle), -$radius_x * sin($x_angle) * sin($angle) + $radius_y * cos($x_angle) * cos($angle)];
    }
}