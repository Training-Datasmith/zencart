<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Module\Edge_Iterator;

use Bacon_Qr_Code\Encoder\Byte_Matrix;
use IteratorAggregate;
use Traversable;
/**
 * Edge iterator based on potrace.
 */
final class Edge_Iterator implements IteratorAggregate
{
    /**
     * @var int[]
     */
    private array $bytes = [];
    private readonly ?int $size;
    private readonly int $width;
    private readonly int $height;
    public function __construct(Byte_Matrix $matrix)
    {
        $this->bytes = iterator_to_array($matrix->get_bytes());
        $this->size = count($this->bytes);
        $this->width = $matrix->get_width();
        $this->height = $matrix->get_height();
    }
    /**
     * @return Traversable<Edge>
     */
    public function getIterator(): Traversable
    {
        $original_bytes = $this->bytes;
        $point = $this->find_next(0, 0);
        while (null !== $point) {
            $edge = $this->find_edge($point[0], $point[1]);
            $this->xor_edge($edge);
            yield $edge;
            $point = $this->find_next($point[0], $point[1]);
        }
        $this->bytes = $original_bytes;
    }
    /**
     * @return int[]|null
     */
    private function find_next(int $x, int $y): ?array
    {
        $i = $this->width * $y + $x;
        while ($i < $this->size && 1 !== $this->bytes[$i]) {
            ++$i;
        }
        if ($i < $this->size) {
            return $this->point_of($i);
        }
        return null;
    }
    private function find_edge(int $x, int $y): Edge
    {
        $edge = new Edge($this->is_set($x, $y));
        $start_x = $x;
        $start_y = $y;
        $dir_x = 0;
        $dir_y = 1;
        while (true) {
            $edge->add_point($x, $y);
            $x += $dir_x;
            $y += $dir_y;
            if ($x === $start_x && $y === $start_y) {
                break;
            }
            $left = $this->is_set($x + ($dir_x + $dir_y - 1) / 2, $y + ($dir_y - $dir_x - 1) / 2);
            $right = $this->is_set($x + ($dir_x - $dir_y - 1) / 2, $y + ($dir_y + $dir_x - 1) / 2);
            if ($right && !$left) {
                $tmp = $dir_x;
                $dir_x = -$dir_y;
                $dir_y = $tmp;
            } elseif ($right) {
                $tmp = $dir_x;
                $dir_x = -$dir_y;
                $dir_y = $tmp;
            } elseif (!$left) {
                $tmp = $dir_x;
                $dir_x = $dir_y;
                $dir_y = -$tmp;
            }
        }
        return $edge;
    }
    private function xor_edge(Edge $path): void
    {
        $points = $path->get_points();
        $y1 = $points[0][1];
        $length = count($points);
        $max_x = $path->get_max_x();
        for ($i = 1; $i < $length; ++$i) {
            $y = $points[$i][1];
            if ($y === $y1) {
                continue;
            }
            $x = $points[$i][0];
            $min_y = min($y1, $y);
            for ($j = $x; $j < $max_x; ++$j) {
                $this->flip($j, $min_y);
            }
            $y1 = $y;
        }
    }
    private function is_set(int $x, int $y): bool
    {
        return $x >= 0 && $x < $this->width && $y >= 0 && $y < $this->height && 1 === $this->bytes[$this->width * $y + $x];
    }
    /**
     * @return int[]
     */
    private function point_of(int $i): array
    {
        $y = intdiv($i, $this->width);
        return [$i - $y * $this->width, $y];
    }
    private function flip(int $x, int $y): void
    {
        $this->bytes[$this->width * $y + $x] = $this->is_set($x, $y) ? 0 : 1;
    }
}