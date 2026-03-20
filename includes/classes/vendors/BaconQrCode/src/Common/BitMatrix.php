<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Common;

use Bacon_Qr_Code\Exception\InvalidArgumentException;
use SplFixedArray;
/**
 * Bit matrix.
 *
 * Represents a 2D matrix of bits. In function arguments below, and throughout
 * the common module, x is the column position, and y is the row position. The
 * ordering is always x, y. The origin is at the top-left.
 */
class Bit_Matrix
{
    /**
     * Width of the bit matrix.
     */
    private readonly int $width;
    /**
     * Height of the bit matrix.
     */
    private readonly ?int $height;
    /**
     * Size in bits of each individual row.
     */
    private readonly int $row_size;
    /**
     * Bits representation.
     *
     * @var SplFixedArray<int>
     */
    private SplFixedArray $bits;
    /**
     * @throws InvalidArgumentException if a dimension is smaller than zero
     */
    public function __construct(int $width, ?int $height = null)
    {
        if (null === $height) {
            $height = $width;
        }
        if ($width < 1 || $height < 1) {
            throw new InvalidArgumentException('Both dimensions must be greater than zero');
        }
        $this->width = $width;
        $this->height = $height;
        $this->row_size = $width + 31 >> 5;
        $this->bits = SplFixedArray::from_array(array_fill(0, $this->row_size * $height, 0));
    }
    /**
     * Gets the requested bit, where true means black.
     */
    public function get(int $x, int $y): bool
    {
        $offset = $y * $this->row_size + ($x >> 5);
        return 0 !== (Bit_Utils::unsigned_right_shift($this->bits[$offset], $x & 0x1f) & 1);
    }
    /**
     * Sets the given bit to true.
     */
    public function set(int $x, int $y): void
    {
        $offset = $y * $this->row_size + ($x >> 5);
        $this->bits[$offset] = $this->bits[$offset] | 1 << ($x & 0x1f);
    }
    /**
     * Flips the given bit.
     */
    public function flip(int $x, int $y): void
    {
        $offset = $y * $this->row_size + ($x >> 5);
        $this->bits[$offset] = $this->bits[$offset] ^ 1 << ($x & 0x1f);
    }
    /**
     * Clears all bits (set to false).
     */
    public function clear(): void
    {
        $max = count($this->bits);
        for ($i = 0; $i < $max; ++$i) {
            $this->bits[$i] = 0;
        }
    }
    /**
     * Sets a square region of the bit matrix to true.
     *
     * @throws InvalidArgumentException if left or top are negative
     * @throws InvalidArgumentException if width or height are smaller than 1
     * @throws InvalidArgumentException if region does not fit into the matix
     */
    public function set_region(int $left, int $top, int $width, int $height): void
    {
        if ($top < 0 || $left < 0) {
            throw new InvalidArgumentException('Left and top must be non-negative');
        }
        if ($height < 1 || $width < 1) {
            throw new InvalidArgumentException('Width and height must be at least 1');
        }
        $right = $left + $width;
        $bottom = $top + $height;
        if ($bottom > $this->height || $right > $this->width) {
            throw new InvalidArgumentException('The region must fit inside the matrix');
        }
        for ($y = $top; $y < $bottom; ++$y) {
            $offset = $y * $this->row_size;
            for ($x = $left; $x < $right; ++$x) {
                $index = $offset + ($x >> 5);
                $this->bits[$index] = $this->bits[$index] | 1 << ($x & 0x1f);
            }
        }
    }
    /**
     * A fast method to retrieve one row of data from the matrix as a BitArray.
     */
    public function get_row(int $y, ?Bit_Array $row = null): Bit_Array
    {
        if (null === $row || $row->get_size() < $this->width) {
            $row = new Bit_Array($this->width);
        }
        $offset = $y * $this->row_size;
        for ($x = 0; $x < $this->row_size; ++$x) {
            $row->set_bulk($x << 5, $this->bits[$offset + $x]);
        }
        return $row;
    }
    /**
     * Sets a row of data from a BitArray.
     */
    public function set_row(int $y, Bit_Array $row): void
    {
        $bits = $row->get_bit_array();
        for ($i = 0; $i < $this->row_size; ++$i) {
            $this->bits[$y * $this->row_size + $i] = $bits[$i];
        }
    }
    /**
     * This is useful in detecting the enclosing rectangle of a 'pure' barcode.
     *
     * @return int[]|null
     */
    public function get_enclosing_rectangle(): ?array
    {
        $left = $this->width;
        $top = $this->height;
        $right = -1;
        $bottom = -1;
        for ($y = 0; $y < $this->height; ++$y) {
            for ($x32 = 0; $x32 < $this->row_size; ++$x32) {
                $bits = $this->bits[$y * $this->row_size + $x32];
                if (0 !== $bits) {
                    if ($y < $top) {
                        $top = $y;
                    }
                    if ($y > $bottom) {
                        $bottom = $y;
                    }
                    if ($x32 * 32 < $left) {
                        $bit = 0;
                        while ($bits << 31 - $bit === 0) {
                            $bit++;
                        }
                        if ($x32 * 32 + $bit < $left) {
                            $left = $x32 * 32 + $bit;
                        }
                    }
                }
                if ($x32 * 32 + 31 > $right) {
                    $bit = 31;
                    while (0 === Bit_Utils::unsigned_right_shift($bits, $bit)) {
                        --$bit;
                    }
                    if ($x32 * 32 + $bit > $right) {
                        $right = $x32 * 32 + $bit;
                    }
                }
            }
        }
        $width = $right - $left;
        $height = $bottom - $top;
        if ($width < 0 || $height < 0) {
            return null;
        }
        return [$left, $top, $width, $height];
    }
    /**
     * Gets the most top left set bit.
     *
     * This is useful in detecting a corner of a 'pure' barcode.
     *
     * @return int[]|null
     */
    public function get_top_left_on_bit(): ?array
    {
        $bits_offset = 0;
        while ($bits_offset < count($this->bits) && 0 === $this->bits[$bits_offset]) {
            ++$bits_offset;
        }
        if (count($this->bits) === $bits_offset) {
            return null;
        }
        $x = intdiv($bits_offset, $this->row_size);
        $y = $bits_offset % $this->row_size << 5;
        $bits = $this->bits[$bits_offset];
        $bit = 0;
        while (0 === $bits << 31 - $bit) {
            ++$bit;
        }
        $x += $bit;
        return [$x, $y];
    }
    /**
     * Gets the most bottom right set bit.
     *
     * This is useful in detecting a corner of a 'pure' barcode.
     *
     * @return int[]|null
     */
    public function get_bottom_right_on_bit(): ?array
    {
        $bits_offset = count($this->bits) - 1;
        while ($bits_offset >= 0 && 0 === $this->bits[$bits_offset]) {
            --$bits_offset;
        }
        if ($bits_offset < 0) {
            return null;
        }
        $x = intdiv($bits_offset, $this->row_size);
        $y = $bits_offset % $this->row_size << 5;
        $bits = $this->bits[$bits_offset];
        $bit = 0;
        while (0 === Bit_Utils::unsigned_right_shift($bits, $bit)) {
            --$bit;
        }
        $x += $bit;
        return [$x, $y];
    }
    /**
     * Gets the width of the matrix,
     */
    public function get_width(): int
    {
        return $this->width;
    }
    /**
     * Gets the height of the matrix.
     */
    public function get_height(): int
    {
        return $this->height;
    }
}