<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Encoder;

use Bacon_Qr_Code\Common\Bit_Array;
use Bacon_Qr_Code\Common\Error_Correction_Level;
use Bacon_Qr_Code\Common\Version;
use Bacon_Qr_Code\Exception\RuntimeException;
use Bacon_Qr_Code\Exception\Writer_Exception;
/**
 * Matrix utility.
 */
final class Matrix_Util
{
    /**
     * Position detection pattern.
     */
    private const POSITION_DETECTION_PATTERN = [[1, 1, 1, 1, 1, 1, 1], [1, 0, 0, 0, 0, 0, 1], [1, 0, 1, 1, 1, 0, 1], [1, 0, 1, 1, 1, 0, 1], [1, 0, 1, 1, 1, 0, 1], [1, 0, 0, 0, 0, 0, 1], [1, 1, 1, 1, 1, 1, 1]];
    /**
     * Position adjustment pattern.
     */
    private const POSITION_ADJUSTMENT_PATTERN = [[1, 1, 1, 1, 1], [1, 0, 0, 0, 1], [1, 0, 1, 0, 1], [1, 0, 0, 0, 1], [1, 1, 1, 1, 1]];
    /**
     * Coordinates for position adjustment patterns for each version.
     */
    private const POSITION_ADJUSTMENT_PATTERN_COORDINATE_TABLE = [
        [null, null, null, null, null, null, null],
        // Version 1
        [6, 18, null, null, null, null, null],
        // Version 2
        [6, 22, null, null, null, null, null],
        // Version 3
        [6, 26, null, null, null, null, null],
        // Version 4
        [6, 30, null, null, null, null, null],
        // Version 5
        [6, 34, null, null, null, null, null],
        // Version 6
        [6, 22, 38, null, null, null, null],
        // Version 7
        [6, 24, 42, null, null, null, null],
        // Version 8
        [6, 26, 46, null, null, null, null],
        // Version 9
        [6, 28, 50, null, null, null, null],
        // Version 10
        [6, 30, 54, null, null, null, null],
        // Version 11
        [6, 32, 58, null, null, null, null],
        // Version 12
        [6, 34, 62, null, null, null, null],
        // Version 13
        [6, 26, 46, 66, null, null, null],
        // Version 14
        [6, 26, 48, 70, null, null, null],
        // Version 15
        [6, 26, 50, 74, null, null, null],
        // Version 16
        [6, 30, 54, 78, null, null, null],
        // Version 17
        [6, 30, 56, 82, null, null, null],
        // Version 18
        [6, 30, 58, 86, null, null, null],
        // Version 19
        [6, 34, 62, 90, null, null, null],
        // Version 20
        [6, 28, 50, 72, 94, null, null],
        // Version 21
        [6, 26, 50, 74, 98, null, null],
        // Version 22
        [6, 30, 54, 78, 102, null, null],
        // Version 23
        [6, 28, 54, 80, 106, null, null],
        // Version 24
        [6, 32, 58, 84, 110, null, null],
        // Version 25
        [6, 30, 58, 86, 114, null, null],
        // Version 26
        [6, 34, 62, 90, 118, null, null],
        // Version 27
        [6, 26, 50, 74, 98, 122, null],
        // Version 28
        [6, 30, 54, 78, 102, 126, null],
        // Version 29
        [6, 26, 52, 78, 104, 130, null],
        // Version 30
        [6, 30, 56, 82, 108, 134, null],
        // Version 31
        [6, 34, 60, 86, 112, 138, null],
        // Version 32
        [6, 30, 58, 86, 114, 142, null],
        // Version 33
        [6, 34, 62, 90, 118, 146, null],
        // Version 34
        [6, 30, 54, 78, 102, 126, 150],
        // Version 35
        [6, 24, 50, 76, 102, 128, 154],
        // Version 36
        [6, 28, 54, 80, 106, 132, 158],
        // Version 37
        [6, 32, 58, 84, 110, 136, 162],
        // Version 38
        [6, 26, 54, 82, 110, 138, 166],
        // Version 39
        [6, 30, 58, 86, 114, 142, 170],
    ];
    /**
     * Type information coordinates.
     */
    private const TYPE_INFO_COORDINATES = [[8, 0], [8, 1], [8, 2], [8, 3], [8, 4], [8, 5], [8, 7], [8, 8], [7, 8], [5, 8], [4, 8], [3, 8], [2, 8], [1, 8], [0, 8]];
    /**
     * Version information polynomial.
     */
    private const VERSION_INFO_POLY = 0x1f25;
    /**
     * Type information polynomial.
     */
    private const TYPE_INFO_POLY = 0x537;
    /**
     * Type information mask pattern.
     */
    private const TYPE_INFO_MASK_PATTERN = 0x5412;
    /**
     * Clears a given matrix.
     */
    public static function clear_matrix(Byte_Matrix $matrix): void
    {
        $matrix->clear(-1);
    }
    /**
     * Builds a complete matrix.
     */
    public static function build_matrix(Bit_Array $data_bits, Error_Correction_Level $level, Version $version, int $mask_pattern, Byte_Matrix $matrix): void
    {
        self::clear_matrix($matrix);
        self::embed_basic_patterns($version, $matrix);
        self::embed_type_info($level, $mask_pattern, $matrix);
        self::maybe_embed_version_info($version, $matrix);
        self::embed_data_bits($data_bits, $mask_pattern, $matrix);
    }
    /**
     * Removes the position detection patterns from a matrix.
     *
     * This can be useful if you need to render those patterns separately.
     */
    public static function remove_position_detection_patterns(Byte_Matrix $matrix): void
    {
        $pdp_width = count(self::POSITION_DETECTION_PATTERN[0]);
        self::remove_position_detection_pattern(0, 0, $matrix);
        self::remove_position_detection_pattern($matrix->get_width() - $pdp_width, 0, $matrix);
        self::remove_position_detection_pattern(0, $matrix->get_width() - $pdp_width, $matrix);
    }
    /**
     * Embeds type information into a matrix.
     */
    private static function embed_type_info(Error_Correction_Level $level, int $mask_pattern, Byte_Matrix $matrix): void
    {
        $type_info_bits = new Bit_Array();
        self::make_type_info_bits($level, $mask_pattern, $type_info_bits);
        $type_info_bits_size = $type_info_bits->get_size();
        for ($i = 0; $i < $type_info_bits_size; ++$i) {
            $bit = $type_info_bits->get($type_info_bits_size - 1 - $i);
            $x1 = self::TYPE_INFO_COORDINATES[$i][0];
            $y1 = self::TYPE_INFO_COORDINATES[$i][1];
            $matrix->set($x1, $y1, (int) $bit);
            if ($i < 8) {
                $x2 = $matrix->get_width() - $i - 1;
                $y2 = 8;
            } else {
                $x2 = 8;
                $y2 = $matrix->get_height() - 7 + ($i - 8);
            }
            $matrix->set($x2, $y2, (int) $bit);
        }
    }
    /**
     * Generates type information bits and appends them to a bit array.
     *
     * @throws RuntimeException if bit array resulted in invalid size
     */
    private static function make_type_info_bits(Error_Correction_Level $level, int $mask_pattern, Bit_Array $bits): void
    {
        $type_info = $level->get_bits() << 3 | $mask_pattern;
        $bits->append_bits($type_info, 5);
        $bch_code = self::calculate_bch_code($type_info, self::TYPE_INFO_POLY);
        $bits->append_bits($bch_code, 10);
        $mask_bits = new Bit_Array();
        $mask_bits->append_bits(self::TYPE_INFO_MASK_PATTERN, 15);
        $bits->xor_bits($mask_bits);
        if (15 !== $bits->get_size()) {
            throw new RuntimeException('Bit array resulted in invalid size: ' . $bits->get_size());
        }
    }
    /**
     * Embeds version information if required.
     */
    private static function maybe_embed_version_info(Version $version, Byte_Matrix $matrix): void
    {
        if ($version->get_version_number() < 7) {
            return;
        }
        $version_info_bits = new Bit_Array();
        self::make_version_info_bits($version, $version_info_bits);
        $bit_index = 6 * 3 - 1;
        for ($i = 0; $i < 6; ++$i) {
            for ($j = 0; $j < 3; ++$j) {
                $bit = $version_info_bits->get($bit_index);
                --$bit_index;
                $matrix->set($i, $matrix->get_height() - 11 + $j, (int) $bit);
                $matrix->set($matrix->get_height() - 11 + $j, $i, (int) $bit);
            }
        }
    }
    /**
     * Generates version information bits and appends them to a bit array.
     *
     * @throws RuntimeException if bit array resulted in invalid size
     */
    private static function make_version_info_bits(Version $version, Bit_Array $bits): void
    {
        $bits->append_bits($version->get_version_number(), 6);
        $bch_code = self::calculate_bch_code($version->get_version_number(), self::VERSION_INFO_POLY);
        $bits->append_bits($bch_code, 12);
        if (18 !== $bits->get_size()) {
            throw new RuntimeException('Bit array resulted in invalid size: ' . $bits->get_size());
        }
    }
    /**
     * Calculates the BCH code for a value and a polynomial.
     */
    private static function calculate_bch_code(int $value, int $poly): int
    {
        $msb_set_in_poly = self::find_msb_set($poly);
        $value <<= $msb_set_in_poly - 1;
        while (self::find_msb_set($value) >= $msb_set_in_poly) {
            $value ^= $poly << self::find_msb_set($value) - $msb_set_in_poly;
        }
        return $value;
    }
    /**
     * Finds and MSB set.
     */
    private static function find_msb_set(int $value): int
    {
        $num_digits = 0;
        while (0 !== $value) {
            $value >>= 1;
            ++$num_digits;
        }
        return $num_digits;
    }
    /**
     * Embeds basic patterns into a matrix.
     */
    private static function embed_basic_patterns(Version $version, Byte_Matrix $matrix): void
    {
        self::embed_position_detection_patterns_and_separators($matrix);
        self::embed_dark_dot_at_left_bottom_corner($matrix);
        self::maybe_embed_position_adjustment_patterns($version, $matrix);
        self::embed_timing_patterns($matrix);
    }
    /**
     * Embeds position detection patterns and separators into a byte matrix.
     */
    private static function embed_position_detection_patterns_and_separators(Byte_Matrix $matrix): void
    {
        $pdp_width = count(self::POSITION_DETECTION_PATTERN[0]);
        self::embed_position_detection_pattern(0, 0, $matrix);
        self::embed_position_detection_pattern($matrix->get_width() - $pdp_width, 0, $matrix);
        self::embed_position_detection_pattern(0, $matrix->get_width() - $pdp_width, $matrix);
        $hsp_width = 8;
        self::embed_horizontal_separation_pattern(0, $hsp_width - 1, $matrix);
        self::embed_horizontal_separation_pattern($matrix->get_width() - $hsp_width, $hsp_width - 1, $matrix);
        self::embed_horizontal_separation_pattern(0, $matrix->get_width() - $hsp_width, $matrix);
        $vsp_size = 7;
        self::embed_vertical_separation_pattern($vsp_size, 0, $matrix);
        self::embed_vertical_separation_pattern($matrix->get_height() - $vsp_size - 1, 0, $matrix);
        self::embed_vertical_separation_pattern($vsp_size, $matrix->get_height() - $vsp_size, $matrix);
    }
    /**
     * Embeds a single position detection pattern into a byte matrix.
     */
    private static function embed_position_detection_pattern(int $x_start, int $y_start, Byte_Matrix $matrix): void
    {
        for ($y = 0; $y < 7; ++$y) {
            for ($x = 0; $x < 7; ++$x) {
                $matrix->set($x_start + $x, $y_start + $y, self::POSITION_DETECTION_PATTERN[$y][$x]);
            }
        }
    }
    private static function remove_position_detection_pattern(int $x_start, int $y_start, Byte_Matrix $matrix): void
    {
        for ($y = 0; $y < 7; ++$y) {
            for ($x = 0; $x < 7; ++$x) {
                $matrix->set($x_start + $x, $y_start + $y, 0);
            }
        }
    }
    /**
     * Embeds a single horizontal separation pattern.
     *
     * @throws RuntimeException if a byte was already set
     */
    private static function embed_horizontal_separation_pattern(int $x_start, int $y_start, Byte_Matrix $matrix): void
    {
        for ($x = 0; $x < 8; $x++) {
            if (-1 !== $matrix->get($x_start + $x, $y_start)) {
                throw new RuntimeException('Byte already set');
            }
            $matrix->set($x_start + $x, $y_start, 0);
        }
    }
    /**
     * Embeds a single vertical separation pattern.
     *
     * @throws RuntimeException if a byte was already set
     */
    private static function embed_vertical_separation_pattern(int $x_start, int $y_start, Byte_Matrix $matrix): void
    {
        for ($y = 0; $y < 7; $y++) {
            if (-1 !== $matrix->get($x_start, $y_start + $y)) {
                throw new RuntimeException('Byte already set');
            }
            $matrix->set($x_start, $y_start + $y, 0);
        }
    }
    /**
     * Embeds a dot at the left bottom corner.
     *
     * @throws RuntimeException if a byte was already set to 0
     */
    private static function embed_dark_dot_at_left_bottom_corner(Byte_Matrix $matrix): void
    {
        if (0 === $matrix->get(8, $matrix->get_height() - 8)) {
            throw new RuntimeException('Byte already set to 0');
        }
        $matrix->set(8, $matrix->get_height() - 8, 1);
    }
    /**
     * Embeds position adjustment patterns if required.
     */
    private static function maybe_embed_position_adjustment_patterns(Version $version, Byte_Matrix $matrix): void
    {
        if ($version->get_version_number() < 2) {
            return;
        }
        $index = $version->get_version_number() - 1;
        $coordinates = self::POSITION_ADJUSTMENT_PATTERN_COORDINATE_TABLE[$index];
        $num_coordinates = count($coordinates);
        for ($i = 0; $i < $num_coordinates; ++$i) {
            for ($j = 0; $j < $num_coordinates; ++$j) {
                $y = $coordinates[$i];
                $x = $coordinates[$j];
                if (null === $x) {
                    continue;
                }
                if (null === $y) {
                    continue;
                }
                if (-1 === $matrix->get($x, $y)) {
                    self::embed_position_adjustment_pattern($x - 2, $y - 2, $matrix);
                }
            }
        }
    }
    /**
     * Embeds a single position adjustment pattern.
     */
    private static function embed_position_adjustment_pattern(int $x_start, int $y_start, Byte_Matrix $matrix): void
    {
        for ($y = 0; $y < 5; $y++) {
            for ($x = 0; $x < 5; $x++) {
                $matrix->set($x_start + $x, $y_start + $y, self::POSITION_ADJUSTMENT_PATTERN[$y][$x]);
            }
        }
    }
    /**
     * Embeds timing patterns into a matrix.
     */
    private static function embed_timing_patterns(Byte_Matrix $matrix): void
    {
        $matrix_width = $matrix->get_width();
        for ($i = 8; $i < $matrix_width - 8; ++$i) {
            $bit = ($i + 1) % 2;
            if (-1 === $matrix->get($i, 6)) {
                $matrix->set($i, 6, $bit);
            }
            if (-1 === $matrix->get(6, $i)) {
                $matrix->set(6, $i, $bit);
            }
        }
    }
    /**
     * Embeds "dataBits" using "getMaskPattern".
     *
     * For debugging purposes, it skips masking process if "getMaskPattern" is -1. See 8.7 of JISX0510:2004 (p.38) for
     * how to embed data bits.
     *
     * @throws WriterException if not all bits could be consumed
     */
    private static function embed_data_bits(Bit_Array $data_bits, int $mask_pattern, Byte_Matrix $matrix): void
    {
        $bit_index = 0;
        $direction = -1;
        // Start from the right bottom cell.
        $x = $matrix->get_width() - 1;
        $y = $matrix->get_height() - 1;
        while ($x > 0) {
            // Skip vertical timing pattern.
            if (6 === $x) {
                --$x;
            }
            while ($y >= 0 && $y < $matrix->get_height()) {
                for ($i = 0; $i < 2; $i++) {
                    $xx = $x - $i;
                    // Skip the cell if it's not empty.
                    if (-1 !== $matrix->get($xx, $y)) {
                        continue;
                    }
                    if ($bit_index < $data_bits->get_size()) {
                        $bit = $data_bits->get($bit_index);
                        ++$bit_index;
                    } else {
                        // Padding bit. If there is no bit left, we'll fill the
                        // left cells with 0, as described in 8.4.9 of
                        // JISX0510:2004 (p. 24).
                        $bit = false;
                    }
                    // Skip masking if maskPattern is -1.
                    if (-1 !== $mask_pattern && Mask_Util::get_data_mask_bit($mask_pattern, $xx, $y)) {
                        $bit = !$bit;
                    }
                    $matrix->set($xx, $y, (int) $bit);
                }
                $y += $direction;
            }
            $direction = -$direction;
            $y += $direction;
            $x -= 2;
        }
        // All bits should be consumed
        if ($data_bits->get_size() !== $bit_index) {
            throw new Writer_Exception('Not all bits consumed (' . $bit_index . ' out of ' . $data_bits->get_size() . ')');
        }
    }
}