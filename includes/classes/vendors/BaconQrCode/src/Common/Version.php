<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Common;

use Bacon_Qr_Code\Exception\InvalidArgumentException;
use SplFixedArray;
/**
 * Version representation.
 */
final class Version implements \Stringable
{
    private const VERSION_DECODE_INFO = [0x7c94, 0x85bc, 0x9a99, 0xa4d3, 0xbbf6, 0xc762, 0xd847, 0xe60d, 0xf928, 0x10b78, 0x1145d, 0x12a17, 0x13532, 0x149a6, 0x15683, 0x168c9, 0x177ec, 0x18ec4, 0x191e1, 0x1afab, 0x1b08e, 0x1cc1a, 0x1d33f, 0x1ed75, 0x1f250, 0x209d5, 0x216f0, 0x228ba, 0x2379f, 0x24b0b, 0x2542e, 0x26a64, 0x27541, 0x28c69];
    /**
     * Error correction blocks.
     *
     * @var EcBlocks[]
     */
    private array $ec_blocks;
    /**
     * Total number of codewords.
     */
    private readonly null|int|float $total_codewords;
    /**
     * Cached version instances.
     *
     * @var array<int, self>|null
     */
    private static ?array $versions = null;
    /**
     * @param int[] $alignmentPatternCenters
     */
    private function __construct(
        /**
         * Version number of this version.
         */
        private readonly int $version_number,
        /**
         * Alignment pattern centers.
         */
        private SplFixedArray|array $alignment_pattern_centers,
        Ec_Blocks ...$ec_blocks
    )
    {
        $this->ec_blocks = $ec_blocks;
        $total_codewords = 0;
        $ec_codewords = $ec_blocks[0]->get_ec_codewords_per_block();
        foreach ($ec_blocks[0]->get_ec_blocks() as $ec_block) {
            $total_codewords += $ec_block->get_count() * ($ec_block->get_data_codewords() + $ec_codewords);
        }
        $this->total_codewords = $total_codewords;
    }
    /**
     * Returns the version number.
     */
    public function get_version_number(): int
    {
        return $this->version_number;
    }
    /**
     * Returns the alignment pattern centers.
     *
     * @return int[]
     */
    public function get_alignment_pattern_centers(): array
    {
        return $this->alignment_pattern_centers;
    }
    /**
     * Returns the total number of codewords.
     */
    public function get_total_codewords(): int
    {
        return $this->total_codewords;
    }
    /**
     * Calculates the dimension for the current version.
     */
    public function get_dimension_for_version(): int
    {
        return 17 + 4 * $this->version_number;
    }
    /**
     * Returns the number of EC blocks for a specific EC level.
     */
    public function get_ec_blocks_for_level(Error_Correction_Level $ec_level): Ec_Blocks
    {
        return $this->ec_blocks[$ec_level->ordinal()];
    }
    /**
     * Gets a provisional version number for a specific dimension.
     *
     * @throws InvalidArgumentException if dimension is not 1 mod 4
     */
    public static function get_provisional_version_for_dimension(int $dimension): self
    {
        if (1 !== $dimension % 4) {
            throw new InvalidArgumentException('Dimension is not 1 mod 4');
        }
        return self::get_version_for_number(intdiv($dimension - 17, 4));
    }
    /**
     * Gets a version instance for a specific version number.
     *
     * @throws InvalidArgumentException if version number is out of range
     */
    public static function get_version_for_number(int $version_number): self
    {
        if ($version_number < 1 || $version_number > 40) {
            throw new InvalidArgumentException('Version number must be between 1 and 40');
        }
        return self::versions()[$version_number - 1];
    }
    /**
     * Decodes version information from an integer and returns the version.
     */
    public static function decode_version_information(int $version_bits): ?self
    {
        $best_difference = PHP_INT_MAX;
        $best_version = 0;
        foreach (self::VERSION_DECODE_INFO as $i => $target_version) {
            if ($target_version === $version_bits) {
                return self::get_version_for_number($i + 7);
            }
            $bits_difference = Format_Information::num_bits_differing($version_bits, $target_version);
            if ($bits_difference < $best_difference) {
                $best_version = $i + 7;
                $best_difference = $bits_difference;
            }
        }
        if ($best_difference <= 3) {
            return self::get_version_for_number($best_version);
        }
        return null;
    }
    /**
     * Builds the function pattern for the current version.
     */
    public function build_function_pattern(): Bit_Matrix
    {
        $dimension = $this->get_dimension_for_version();
        $bit_matrix = new Bit_Matrix($dimension);
        // Top left finder pattern + separator + format
        $bit_matrix->set_region(0, 0, 9, 9);
        // Top right finder pattern + separator + format
        $bit_matrix->set_region($dimension - 8, 0, 8, 9);
        // Bottom left finder pattern + separator + format
        $bit_matrix->set_region(0, $dimension - 8, 9, 8);
        // Alignment patterns
        $max = count($this->alignment_pattern_centers);
        for ($x = 0; $x < $max; ++$x) {
            $i = $this->alignment_pattern_centers[$x] - 2;
            for ($y = 0; $y < $max; ++$y) {
                if ($x === 0 && ($y === 0 || $y === $max - 1)) {
                    // No alignment patterns near the three finder paterns
                    continue;
                }
                if ($x === $max - 1 && $y === 0) {
                    // No alignment patterns near the three finder paterns
                    continue;
                }
                $bit_matrix->set_region($this->alignment_pattern_centers[$y] - 2, $i, 5, 5);
            }
        }
        // Vertical timing pattern
        $bit_matrix->set_region(6, 9, 1, $dimension - 17);
        // Horizontal timing pattern
        $bit_matrix->set_region(9, 6, $dimension - 17, 1);
        if ($this->version_number > 6) {
            // Version info, top right
            $bit_matrix->set_region($dimension - 11, 0, 3, 6);
            // Version info, bottom left
            $bit_matrix->set_region(0, $dimension - 11, 6, 3);
        }
        return $bit_matrix;
    }
    /**
     * Returns a string representation for the version.
     */
    public function __toString(): string
    {
        return (string) $this->version_number;
    }
    /**
     * Build and cache a specific version.
     *
     * See ISO 18004:2006 6.5.1 Table 9.
     *
     * @return array<int, self>
     */
    private static function versions(): array
    {
        if (null !== self::$versions) {
            return self::$versions;
        }
        return self::$versions = [new self(1, [], new Ec_Blocks(7, new Ec_Block(1, 19)), new Ec_Blocks(10, new Ec_Block(1, 16)), new Ec_Blocks(13, new Ec_Block(1, 13)), new Ec_Blocks(17, new Ec_Block(1, 9))), new self(2, [6, 18], new Ec_Blocks(10, new Ec_Block(1, 34)), new Ec_Blocks(16, new Ec_Block(1, 28)), new Ec_Blocks(22, new Ec_Block(1, 22)), new Ec_Blocks(28, new Ec_Block(1, 16))), new self(3, [6, 22], new Ec_Blocks(15, new Ec_Block(1, 55)), new Ec_Blocks(26, new Ec_Block(1, 44)), new Ec_Blocks(18, new Ec_Block(2, 17)), new Ec_Blocks(22, new Ec_Block(2, 13))), new self(4, [6, 26], new Ec_Blocks(20, new Ec_Block(1, 80)), new Ec_Blocks(18, new Ec_Block(2, 32)), new Ec_Blocks(26, new Ec_Block(2, 24)), new Ec_Blocks(16, new Ec_Block(4, 9))), new self(5, [6, 30], new Ec_Blocks(26, new Ec_Block(1, 108)), new Ec_Blocks(24, new Ec_Block(2, 43)), new Ec_Blocks(18, new Ec_Block(2, 15), new Ec_Block(2, 16)), new Ec_Blocks(22, new Ec_Block(2, 11), new Ec_Block(2, 12))), new self(6, [6, 34], new Ec_Blocks(18, new Ec_Block(2, 68)), new Ec_Blocks(16, new Ec_Block(4, 27)), new Ec_Blocks(24, new Ec_Block(4, 19)), new Ec_Blocks(28, new Ec_Block(4, 15))), new self(7, [6, 22, 38], new Ec_Blocks(20, new Ec_Block(2, 78)), new Ec_Blocks(18, new Ec_Block(4, 31)), new Ec_Blocks(18, new Ec_Block(2, 14), new Ec_Block(4, 15)), new Ec_Blocks(26, new Ec_Block(4, 13), new Ec_Block(1, 14))), new self(8, [6, 24, 42], new Ec_Blocks(24, new Ec_Block(2, 97)), new Ec_Blocks(22, new Ec_Block(2, 38), new Ec_Block(2, 39)), new Ec_Blocks(22, new Ec_Block(4, 18), new Ec_Block(2, 19)), new Ec_Blocks(26, new Ec_Block(4, 14), new Ec_Block(2, 15))), new self(9, [6, 26, 46], new Ec_Blocks(30, new Ec_Block(2, 116)), new Ec_Blocks(22, new Ec_Block(3, 36), new Ec_Block(2, 37)), new Ec_Blocks(20, new Ec_Block(4, 16), new Ec_Block(4, 17)), new Ec_Blocks(24, new Ec_Block(4, 12), new Ec_Block(4, 13))), new self(10, [6, 28, 50], new Ec_Blocks(18, new Ec_Block(2, 68), new Ec_Block(2, 69)), new Ec_Blocks(26, new Ec_Block(4, 43), new Ec_Block(1, 44)), new Ec_Blocks(24, new Ec_Block(6, 19), new Ec_Block(2, 20)), new Ec_Blocks(28, new Ec_Block(6, 15), new Ec_Block(2, 16))), new self(11, [6, 30, 54], new Ec_Blocks(20, new Ec_Block(4, 81)), new Ec_Blocks(30, new Ec_Block(1, 50), new Ec_Block(4, 51)), new Ec_Blocks(28, new Ec_Block(4, 22), new Ec_Block(4, 23)), new Ec_Blocks(24, new Ec_Block(3, 12), new Ec_Block(8, 13))), new self(12, [6, 32, 58], new Ec_Blocks(24, new Ec_Block(2, 92), new Ec_Block(2, 93)), new Ec_Blocks(22, new Ec_Block(6, 36), new Ec_Block(2, 37)), new Ec_Blocks(26, new Ec_Block(4, 20), new Ec_Block(6, 21)), new Ec_Blocks(28, new Ec_Block(7, 14), new Ec_Block(4, 15))), new self(13, [6, 34, 62], new Ec_Blocks(26, new Ec_Block(4, 107)), new Ec_Blocks(22, new Ec_Block(8, 37), new Ec_Block(1, 38)), new Ec_Blocks(24, new Ec_Block(8, 20), new Ec_Block(4, 21)), new Ec_Blocks(22, new Ec_Block(12, 11), new Ec_Block(4, 12))), new self(14, [6, 26, 46, 66], new Ec_Blocks(30, new Ec_Block(3, 115), new Ec_Block(1, 116)), new Ec_Blocks(24, new Ec_Block(4, 40), new Ec_Block(5, 41)), new Ec_Blocks(20, new Ec_Block(11, 16), new Ec_Block(5, 17)), new Ec_Blocks(24, new Ec_Block(11, 12), new Ec_Block(5, 13))), new self(15, [6, 26, 48, 70], new Ec_Blocks(22, new Ec_Block(5, 87), new Ec_Block(1, 88)), new Ec_Blocks(24, new Ec_Block(5, 41), new Ec_Block(5, 42)), new Ec_Blocks(30, new Ec_Block(5, 24), new Ec_Block(7, 25)), new Ec_Blocks(24, new Ec_Block(11, 12), new Ec_Block(7, 13))), new self(16, [6, 26, 50, 74], new Ec_Blocks(24, new Ec_Block(5, 98), new Ec_Block(1, 99)), new Ec_Blocks(28, new Ec_Block(7, 45), new Ec_Block(3, 46)), new Ec_Blocks(24, new Ec_Block(15, 19), new Ec_Block(2, 20)), new Ec_Blocks(30, new Ec_Block(3, 15), new Ec_Block(13, 16))), new self(17, [6, 30, 54, 78], new Ec_Blocks(28, new Ec_Block(1, 107), new Ec_Block(5, 108)), new Ec_Blocks(28, new Ec_Block(10, 46), new Ec_Block(1, 47)), new Ec_Blocks(28, new Ec_Block(1, 22), new Ec_Block(15, 23)), new Ec_Blocks(28, new Ec_Block(2, 14), new Ec_Block(17, 15))), new self(18, [6, 30, 56, 82], new Ec_Blocks(30, new Ec_Block(5, 120), new Ec_Block(1, 121)), new Ec_Blocks(26, new Ec_Block(9, 43), new Ec_Block(4, 44)), new Ec_Blocks(28, new Ec_Block(17, 22), new Ec_Block(1, 23)), new Ec_Blocks(28, new Ec_Block(2, 14), new Ec_Block(19, 15))), new self(19, [6, 30, 58, 86], new Ec_Blocks(28, new Ec_Block(3, 113), new Ec_Block(4, 114)), new Ec_Blocks(26, new Ec_Block(3, 44), new Ec_Block(11, 45)), new Ec_Blocks(26, new Ec_Block(17, 21), new Ec_Block(4, 22)), new Ec_Blocks(26, new Ec_Block(9, 13), new Ec_Block(16, 14))), new self(20, [6, 34, 62, 90], new Ec_Blocks(28, new Ec_Block(3, 107), new Ec_Block(5, 108)), new Ec_Blocks(26, new Ec_Block(3, 41), new Ec_Block(13, 42)), new Ec_Blocks(30, new Ec_Block(15, 24), new Ec_Block(5, 25)), new Ec_Blocks(28, new Ec_Block(15, 15), new Ec_Block(10, 16))), new self(21, [6, 28, 50, 72, 94], new Ec_Blocks(28, new Ec_Block(4, 116), new Ec_Block(4, 117)), new Ec_Blocks(26, new Ec_Block(17, 42)), new Ec_Blocks(28, new Ec_Block(17, 22), new Ec_Block(6, 23)), new Ec_Blocks(30, new Ec_Block(19, 16), new Ec_Block(6, 17))), new self(22, [6, 26, 50, 74, 98], new Ec_Blocks(28, new Ec_Block(2, 111), new Ec_Block(7, 112)), new Ec_Blocks(28, new Ec_Block(17, 46)), new Ec_Blocks(30, new Ec_Block(7, 24), new Ec_Block(16, 25)), new Ec_Blocks(24, new Ec_Block(34, 13))), new self(23, [6, 30, 54, 78, 102], new Ec_Blocks(30, new Ec_Block(4, 121), new Ec_Block(5, 122)), new Ec_Blocks(28, new Ec_Block(4, 47), new Ec_Block(14, 48)), new Ec_Blocks(30, new Ec_Block(11, 24), new Ec_Block(14, 25)), new Ec_Blocks(30, new Ec_Block(16, 15), new Ec_Block(14, 16))), new self(24, [6, 28, 54, 80, 106], new Ec_Blocks(30, new Ec_Block(6, 117), new Ec_Block(4, 118)), new Ec_Blocks(28, new Ec_Block(6, 45), new Ec_Block(14, 46)), new Ec_Blocks(30, new Ec_Block(11, 24), new Ec_Block(16, 25)), new Ec_Blocks(30, new Ec_Block(30, 16), new Ec_Block(2, 17))), new self(25, [6, 32, 58, 84, 110], new Ec_Blocks(26, new Ec_Block(8, 106), new Ec_Block(4, 107)), new Ec_Blocks(28, new Ec_Block(8, 47), new Ec_Block(13, 48)), new Ec_Blocks(30, new Ec_Block(7, 24), new Ec_Block(22, 25)), new Ec_Blocks(30, new Ec_Block(22, 15), new Ec_Block(13, 16))), new self(26, [6, 30, 58, 86, 114], new Ec_Blocks(28, new Ec_Block(10, 114), new Ec_Block(2, 115)), new Ec_Blocks(28, new Ec_Block(19, 46), new Ec_Block(4, 47)), new Ec_Blocks(28, new Ec_Block(28, 22), new Ec_Block(6, 23)), new Ec_Blocks(30, new Ec_Block(33, 16), new Ec_Block(4, 17))), new self(27, [6, 34, 62, 90, 118], new Ec_Blocks(30, new Ec_Block(8, 122), new Ec_Block(4, 123)), new Ec_Blocks(28, new Ec_Block(22, 45), new Ec_Block(3, 46)), new Ec_Blocks(30, new Ec_Block(8, 23), new Ec_Block(26, 24)), new Ec_Blocks(30, new Ec_Block(12, 15), new Ec_Block(28, 16))), new self(28, [6, 26, 50, 74, 98, 122], new Ec_Blocks(30, new Ec_Block(3, 117), new Ec_Block(10, 118)), new Ec_Blocks(28, new Ec_Block(3, 45), new Ec_Block(23, 46)), new Ec_Blocks(30, new Ec_Block(4, 24), new Ec_Block(31, 25)), new Ec_Blocks(30, new Ec_Block(11, 15), new Ec_Block(31, 16))), new self(29, [6, 30, 54, 78, 102, 126], new Ec_Blocks(30, new Ec_Block(7, 116), new Ec_Block(7, 117)), new Ec_Blocks(28, new Ec_Block(21, 45), new Ec_Block(7, 46)), new Ec_Blocks(30, new Ec_Block(1, 23), new Ec_Block(37, 24)), new Ec_Blocks(30, new Ec_Block(19, 15), new Ec_Block(26, 16))), new self(30, [6, 26, 52, 78, 104, 130], new Ec_Blocks(30, new Ec_Block(5, 115), new Ec_Block(10, 116)), new Ec_Blocks(28, new Ec_Block(19, 47), new Ec_Block(10, 48)), new Ec_Blocks(30, new Ec_Block(15, 24), new Ec_Block(25, 25)), new Ec_Blocks(30, new Ec_Block(23, 15), new Ec_Block(25, 16))), new self(31, [6, 30, 56, 82, 108, 134], new Ec_Blocks(30, new Ec_Block(13, 115), new Ec_Block(3, 116)), new Ec_Blocks(28, new Ec_Block(2, 46), new Ec_Block(29, 47)), new Ec_Blocks(30, new Ec_Block(42, 24), new Ec_Block(1, 25)), new Ec_Blocks(30, new Ec_Block(23, 15), new Ec_Block(28, 16))), new self(32, [6, 34, 60, 86, 112, 138], new Ec_Blocks(30, new Ec_Block(17, 115)), new Ec_Blocks(28, new Ec_Block(10, 46), new Ec_Block(23, 47)), new Ec_Blocks(30, new Ec_Block(10, 24), new Ec_Block(35, 25)), new Ec_Blocks(30, new Ec_Block(19, 15), new Ec_Block(35, 16))), new self(33, [6, 30, 58, 86, 114, 142], new Ec_Blocks(30, new Ec_Block(17, 115), new Ec_Block(1, 116)), new Ec_Blocks(28, new Ec_Block(14, 46), new Ec_Block(21, 47)), new Ec_Blocks(30, new Ec_Block(29, 24), new Ec_Block(19, 25)), new Ec_Blocks(30, new Ec_Block(11, 15), new Ec_Block(46, 16))), new self(34, [6, 34, 62, 90, 118, 146], new Ec_Blocks(30, new Ec_Block(13, 115), new Ec_Block(6, 116)), new Ec_Blocks(28, new Ec_Block(14, 46), new Ec_Block(23, 47)), new Ec_Blocks(30, new Ec_Block(44, 24), new Ec_Block(7, 25)), new Ec_Blocks(30, new Ec_Block(59, 16), new Ec_Block(1, 17))), new self(35, [6, 30, 54, 78, 102, 126, 150], new Ec_Blocks(30, new Ec_Block(12, 121), new Ec_Block(7, 122)), new Ec_Blocks(28, new Ec_Block(12, 47), new Ec_Block(26, 48)), new Ec_Blocks(30, new Ec_Block(39, 24), new Ec_Block(14, 25)), new Ec_Blocks(30, new Ec_Block(22, 15), new Ec_Block(41, 16))), new self(36, [6, 24, 50, 76, 102, 128, 154], new Ec_Blocks(30, new Ec_Block(6, 121), new Ec_Block(14, 122)), new Ec_Blocks(28, new Ec_Block(6, 47), new Ec_Block(34, 48)), new Ec_Blocks(30, new Ec_Block(46, 24), new Ec_Block(10, 25)), new Ec_Blocks(30, new Ec_Block(2, 15), new Ec_Block(64, 16))), new self(37, [6, 28, 54, 80, 106, 132, 158], new Ec_Blocks(30, new Ec_Block(17, 122), new Ec_Block(4, 123)), new Ec_Blocks(28, new Ec_Block(29, 46), new Ec_Block(14, 47)), new Ec_Blocks(30, new Ec_Block(49, 24), new Ec_Block(10, 25)), new Ec_Blocks(30, new Ec_Block(24, 15), new Ec_Block(46, 16))), new self(38, [6, 32, 58, 84, 110, 136, 162], new Ec_Blocks(30, new Ec_Block(4, 122), new Ec_Block(18, 123)), new Ec_Blocks(28, new Ec_Block(13, 46), new Ec_Block(32, 47)), new Ec_Blocks(30, new Ec_Block(48, 24), new Ec_Block(14, 25)), new Ec_Blocks(30, new Ec_Block(42, 15), new Ec_Block(32, 16))), new self(39, [6, 26, 54, 82, 110, 138, 166], new Ec_Blocks(30, new Ec_Block(20, 117), new Ec_Block(4, 118)), new Ec_Blocks(28, new Ec_Block(40, 47), new Ec_Block(7, 48)), new Ec_Blocks(30, new Ec_Block(43, 24), new Ec_Block(22, 25)), new Ec_Blocks(30, new Ec_Block(10, 15), new Ec_Block(67, 16))), new self(40, [6, 30, 58, 86, 114, 142, 170], new Ec_Blocks(30, new Ec_Block(19, 118), new Ec_Block(6, 119)), new Ec_Blocks(28, new Ec_Block(18, 47), new Ec_Block(31, 48)), new Ec_Blocks(30, new Ec_Block(34, 24), new Ec_Block(34, 25)), new Ec_Blocks(30, new Ec_Block(20, 15), new Ec_Block(61, 16)))];
    }
}