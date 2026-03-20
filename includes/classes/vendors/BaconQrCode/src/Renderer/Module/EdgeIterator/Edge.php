<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Module\Edge_Iterator;

final class Edge
{
    /**
     * @var array<int[]>
     */
    private array $points = [];
    /**
     * @var array<int[]>|null
     */
    private ?array $simplified_points = null;
    private int $min_x = PHP_INT_MAX;
    private int $min_y = PHP_INT_MAX;
    private int $max_x = -1;
    private int $max_y = -1;
    public function __construct(private readonly bool $positive)
    {
    }
    public function add_point(int $x, int $y): void
    {
        $this->points[] = [$x, $y];
        $this->min_x = min($this->min_x, $x);
        $this->min_y = min($this->min_y, $y);
        $this->max_x = max($this->max_x, $x);
        $this->max_y = max($this->max_y, $y);
    }
    public function is_positive(): bool
    {
        return $this->positive;
    }
    /**
     * @return array<int[]>
     */
    public function get_points(): array
    {
        return $this->points;
    }
    public function get_max_x(): int
    {
        return $this->max_x;
    }
    public function get_simplified_points(): array
    {
        if (null !== $this->simplified_points) {
            return $this->simplified_points;
        }
        $points = [];
        $length = count($this->points);
        for ($i = 0; $i < $length; ++$i) {
            $previous_point = $this->points[(0 === $i ? $length : $i) - 1];
            $next_point = $this->points[($length - 1 === $i ? -1 : $i) + 1];
            $current_point = $this->points[$i];
            if ($previous_point[0] === $current_point[0] && $current_point[0] === $next_point[0]) {
                continue;
            }
            if ($previous_point[1] === $current_point[1] && $current_point[1] === $next_point[1]) {
                continue;
            }
            $points[] = $current_point;
        }
        return $this->simplified_points = $points;
    }
}