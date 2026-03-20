<?php

declare (strict_types=1);
/**
 * BaconQrCode
 *
 * @link      http://github.com/Bacon/BaconQrCode For the canonical source repository
 * @copyright 2013 Ben 'DASPRiD' Scholzen
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
namespace Bacon_Qr_Code\Common;

/**
 * Encapsulates a QR Code's format information, including the data mask used and error correction level.
 */
class Format_Information
{
    /**
     * Mask for format information.
     */
    private const FORMAT_INFO_MASK_QR = 0x5412;
    /**
     * Lookup table for decoding format information.
     *
     * See ISO 18004:2006, Annex C, Table C.1
     */
    private const FORMAT_INFO_DECODE_LOOKUP = [[0x5412, 0x0], [0x5125, 0x1], [0x5e7c, 0x2], [0x5b4b, 0x3], [0x45f9, 0x4], [0x40ce, 0x5], [0x4f97, 0x6], [0x4aa0, 0x7], [0x77c4, 0x8], [0x72f3, 0x9], [0x7daa, 0xa], [0x789d, 0xb], [0x662f, 0xc], [0x6318, 0xd], [0x6c41, 0xe], [0x6976, 0xf], [0x1689, 0x10], [0x13be, 0x11], [0x1ce7, 0x12], [0x19d0, 0x13], [0x762, 0x14], [0x255, 0x15], [0xd0c, 0x16], [0x83b, 0x17], [0x355f, 0x18], [0x3068, 0x19], [0x3f31, 0x1a], [0x3a06, 0x1b], [0x24b4, 0x1c], [0x2183, 0x1d], [0x2eda, 0x1e], [0x2bed, 0x1f]];
    /**
     * Offset i holds the number of 1 bits in the binary representation of i.
     *
     * @var int[]
     */
    private const BITS_SET_IN_HALF_BYTE = [0, 1, 1, 2, 1, 2, 2, 3, 1, 2, 2, 3, 2, 3, 3, 4];
    /**
     * Error correction level.
     */
    private readonly Error_Correction_Level $ec_level;
    private readonly int $data_mask;
    protected function __construct(int $format_info)
    {
        $this->ec_level = Error_Correction_Level::for_bits($format_info >> 3 & 0x3);
        $this->data_mask = $format_info & 0x7;
    }
    /**
     * Checks how many bits are different between two integers.
     */
    public static function num_bits_differing(int $a, int $b): int
    {
        $a ^= $b;
        return self::BITS_SET_IN_HALF_BYTE[$a & 0xf] + self::BITS_SET_IN_HALF_BYTE[Bit_Utils::unsigned_right_shift($a, 4) & 0xf] + self::BITS_SET_IN_HALF_BYTE[Bit_Utils::unsigned_right_shift($a, 8) & 0xf] + self::BITS_SET_IN_HALF_BYTE[Bit_Utils::unsigned_right_shift($a, 12) & 0xf] + self::BITS_SET_IN_HALF_BYTE[Bit_Utils::unsigned_right_shift($a, 16) & 0xf] + self::BITS_SET_IN_HALF_BYTE[Bit_Utils::unsigned_right_shift($a, 20) & 0xf] + self::BITS_SET_IN_HALF_BYTE[Bit_Utils::unsigned_right_shift($a, 24) & 0xf] + self::BITS_SET_IN_HALF_BYTE[Bit_Utils::unsigned_right_shift($a, 28) & 0xf];
    }
    /**
     * Decodes format information.
     */
    public static function decode_format_information(int $masked_format_info1, int $masked_format_info2): ?self
    {
        $format_info = self::do_decode_format_information($masked_format_info1, $masked_format_info2);
        if (null !== $format_info) {
            return $format_info;
        }
        // Should return null, but, some QR codes apparently do not mask this info. Try again by actually masking the
        // pattern first.
        return self::do_decode_format_information($masked_format_info1 ^ self::FORMAT_INFO_MASK_QR, $masked_format_info2 ^ self::FORMAT_INFO_MASK_QR);
    }
    /**
     * Internal method for decoding format information.
     */
    private static function do_decode_format_information(int $masked_format_info1, int $masked_format_info2): ?self
    {
        $best_difference = PHP_INT_MAX;
        $best_format_info = 0;
        foreach (self::FORMAT_INFO_DECODE_LOOKUP as $decode_info) {
            $target_info = $decode_info[0];
            if ($target_info === $masked_format_info1 || $target_info === $masked_format_info2) {
                // Found an exact match
                return new self($decode_info[1]);
            }
            $bits_difference = self::num_bits_differing($masked_format_info1, $target_info);
            if ($bits_difference < $best_difference) {
                $best_format_info = $decode_info[1];
                $best_difference = $bits_difference;
            }
            if ($masked_format_info1 !== $masked_format_info2) {
                // Also try the other option
                $bits_difference = self::num_bits_differing($masked_format_info2, $target_info);
                if ($bits_difference < $best_difference) {
                    $best_format_info = $decode_info[1];
                    $best_difference = $bits_difference;
                }
            }
        }
        // Hamming distance of the 32 masked codes is 7, by construction, so <= 3 bits differing means we found a match.
        if ($best_difference <= 3) {
            return new self($best_format_info);
        }
        return null;
    }
    /**
     * Returns the error correction level.
     */
    public function get_error_correction_level(): Error_Correction_Level
    {
        return $this->ec_level;
    }
    /**
     * Returns the data mask.
     */
    public function get_data_mask(): int
    {
        return $this->data_mask;
    }
    /**
     * Hashes the code of the EC level.
     */
    public function hash_code(): int
    {
        return $this->ec_level->get_bits() << 3 | $this->data_mask;
    }
    /**
     * Verifies if this instance equals another one.
     */
    public function equals(self $other): bool
    {
        return $this->ec_level === $other->ec_level && $this->data_mask === $other->data_mask;
    }
}