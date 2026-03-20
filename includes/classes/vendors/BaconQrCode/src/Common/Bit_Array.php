<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Common;

use Bacon_Qr_Code\Exception\InvalidArgumentException;
use SplFixedArray;
/**
 * A simple, fast array of bits.
 */
final class Bit_Array implements \Stringable
{
    /**
     * Bits represented as an array of integers.
     *
     * @var SplFixedArray<int>
     */
    private SplFixedArray $bits;
    /**
     * Creates a new bit array with a given size.
     */
    public function __construct(private int $size = 0)
    {
        $this->bits = SplFixedArray::from_array(array_fill(0, $this->size + 31 >> 3, 0));
    }
    /**
     * Gets the size in bits.
     */
    public function get_size(): int
    {
        return $this->size;
    }
    /**
     * Gets the size in bytes.
     */
    public function get_size_in_bytes(): int
    {
        return $this->size + 7 >> 3;
    }
    /**
     * Ensures that the array has a minimum capacity.
     */
    public function ensure_capacity(int $size): void
    {
        if ($size > count($this->bits) << 5) {
            $this->bits->set_size($size + 31 >> 5);
        }
    }
    /**
     * Gets a specific bit.
     */
    public function get(int $i): bool
    {
        return 0 !== ($this->bits[$i >> 5] & 1 << ($i & 0x1f));
    }
    /**
     * Sets a specific bit.
     */
    public function set(int $i): void
    {
        $this->bits[$i >> 5] = $this->bits[$i >> 5] | 1 << ($i & 0x1f);
    }
    /**
     * Flips a specific bit.
     */
    public function flip(int $i): void
    {
        $this->bits[$i >> 5] ^= 1 << ($i & 0x1f);
    }
    /**
     * Gets the next set bit position from a given position.
     */
    public function get_next_set(int $from): int
    {
        if ($from >= $this->size) {
            return $this->size;
        }
        $bits_offset = $from >> 5;
        $current_bits = $this->bits[$bits_offset];
        $bits_length = count($this->bits);
        $current_bits &= ~((1 << ($from & 0x1f)) - 1);
        while (0 === $current_bits) {
            if (++$bits_offset === $bits_length) {
                return $this->size;
            }
            $current_bits = $this->bits[$bits_offset];
        }
        $result = ($bits_offset << 5) + Bit_Utils::number_of_trailing_zeros($current_bits);
        return min($result, $this->size);
    }
    /**
     * Gets the next unset bit position from a given position.
     */
    public function get_next_unset(int $from): int
    {
        if ($from >= $this->size) {
            return $this->size;
        }
        $bits_offset = $from >> 5;
        $current_bits = ~$this->bits[$bits_offset];
        $bits_length = count($this->bits);
        $current_bits &= ~((1 << ($from & 0x1f)) - 1);
        while (0 === $current_bits) {
            if (++$bits_offset === $bits_length) {
                return $this->size;
            }
            $current_bits = ~$this->bits[$bits_offset];
        }
        $result = ($bits_offset << 5) + Bit_Utils::number_of_trailing_zeros($current_bits);
        return min($result, $this->size);
    }
    /**
     * Sets a bulk of bits.
     */
    public function set_bulk(int $i, int $new_bits): void
    {
        $this->bits[$i >> 5] = $new_bits;
    }
    /**
     * Sets a range of bits.
     *
     * @throws InvalidArgumentException if end is smaller than start
     */
    public function set_range(int $start, int $end): void
    {
        if ($end < $start) {
            throw new InvalidArgumentException('End must be greater or equal to start');
        }
        if ($end === $start) {
            return;
        }
        --$end;
        $first_int = $start >> 5;
        $last_int = $end >> 5;
        for ($i = $first_int; $i <= $last_int; ++$i) {
            $first_bit = $i > $first_int ? 0 : $start & 0x1f;
            $last_bit = $i < $last_int ? 31 : $end & 0x1f;
            if (0 === $first_bit && 31 === $last_bit) {
                $mask = 0x7fffffff;
            } else {
                $mask = 0;
                for ($j = $first_bit; $j < $last_bit; ++$j) {
                    $mask |= 1 << $j;
                }
            }
            $this->bits[$i] = $this->bits[$i] | $mask;
        }
    }
    /**
     * Clears the bit array, unsetting every bit.
     */
    public function clear(): void
    {
        $bits_length = count($this->bits);
        for ($i = 0; $i < $bits_length; ++$i) {
            $this->bits[$i] = 0;
        }
    }
    /**
     * Checks if a range of bits is set or not set.
     * @throws InvalidArgumentException if end is smaller than start
     */
    public function is_range(int $start, int $end, bool $value): bool
    {
        if ($end < $start) {
            throw new InvalidArgumentException('End must be greater or equal to start');
        }
        if ($end === $start) {
            return true;
        }
        --$end;
        $first_int = $start >> 5;
        $last_int = $end >> 5;
        for ($i = $first_int; $i <= $last_int; ++$i) {
            $first_bit = $i > $first_int ? 0 : $start & 0x1f;
            $last_bit = $i < $last_int ? 31 : $end & 0x1f;
            if (0 === $first_bit && 31 === $last_bit) {
                $mask = 0x7fffffff;
            } else {
                $mask = 0;
                for ($j = $first_bit; $j <= $last_bit; ++$j) {
                    $mask |= 1 << $j;
                }
            }
            if (($this->bits[$i] & $mask) !== ($value ? $mask : 0)) {
                return false;
            }
        }
        return true;
    }
    /**
     * Appends a bit to the array.
     */
    public function append_bit(bool $bit): void
    {
        $this->ensure_capacity($this->size + 1);
        if ($bit) {
            $this->bits[$this->size >> 5] = $this->bits[$this->size >> 5] | 1 << ($this->size & 0x1f);
        }
        ++$this->size;
    }
    /**
     * Appends a number of bits (up to 32) to the array.
     * @throws InvalidArgumentException if num bits is not between 0 and 32
     */
    public function append_bits(int $value, int $num_bits): void
    {
        if ($num_bits < 0 || $num_bits > 32) {
            throw new InvalidArgumentException('Num bits must be between 0 and 32');
        }
        $this->ensure_capacity($this->size + $num_bits);
        for ($num_bits_left = $num_bits; $num_bits_left > 0; $num_bits_left--) {
            $this->append_bit(($value >> $num_bits_left - 1 & 0x1) === 1);
        }
    }
    /**
     * Appends another bit array to this array.
     */
    public function append_bit_array(self $other): void
    {
        $other_size = $other->get_size();
        $this->ensure_capacity($this->size + $other->get_size());
        for ($i = 0; $i < $other_size; ++$i) {
            $this->append_bit($other->get($i));
        }
    }
    /**
     * Makes an exclusive-or comparision on the current bit array.
     *
     * @throws InvalidArgumentException if sizes don't match
     */
    public function xor_bits(self $other): void
    {
        $bits_length = count($this->bits);
        $other_bits = $other->get_bit_array();
        if ($bits_length !== count($other_bits)) {
            throw new InvalidArgumentException('Sizes don\'t match');
        }
        for ($i = 0; $i < $bits_length; ++$i) {
            $this->bits[$i] = $this->bits[$i] ^ $other_bits[$i];
        }
    }
    /**
     * Converts the bit array to a byte array.
     *
     * @return SplFixedArray<int>
     */
    public function to_bytes(int $bit_offset, int $num_bytes): SplFixedArray
    {
        $bytes = new SplFixedArray($num_bytes);
        for ($i = 0; $i < $num_bytes; ++$i) {
            $byte = 0;
            for ($j = 0; $j < 8; ++$j) {
                if ($this->get($bit_offset)) {
                    $byte |= 1 << 7 - $j;
                }
                ++$bit_offset;
            }
            $bytes[$i] = $byte;
        }
        return $bytes;
    }
    /**
     * Gets the internal bit array.
     *
     * @return SplFixedArray<int>
     */
    public function get_bit_array(): SplFixedArray
    {
        return $this->bits;
    }
    /**
     * Reverses the array.
     */
    public function reverse(): void
    {
        $new_bits = new SplFixedArray(count($this->bits));
        for ($i = 0; $i < $this->size; ++$i) {
            if ($this->get($this->size - $i - 1)) {
                $new_bits[$i >> 5] = $new_bits[$i >> 5] | 1 << ($i & 0x1f);
            }
        }
        $this->bits = $new_bits;
    }
    /**
     * Returns a string representation of the bit array.
     */
    public function __toString(): string
    {
        $result = '';
        for ($i = 0; $i < $this->size; ++$i) {
            if (0 === ($i & 0x7)) {
                $result .= ' ';
            }
            $result .= $this->get($i) ? 'X' : '.';
        }
        return $result;
    }
}