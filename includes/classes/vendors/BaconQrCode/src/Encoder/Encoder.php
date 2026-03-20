<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Encoder;

use Bacon_Qr_Code\Common\Bit_Array;
use Bacon_Qr_Code\Common\Character_Set_Eci;
use Bacon_Qr_Code\Common\Error_Correction_Level;
use Bacon_Qr_Code\Common\Mode;
use Bacon_Qr_Code\Common\Reed_Solomon_Codec;
use Bacon_Qr_Code\Common\Version;
use Bacon_Qr_Code\Exception\Writer_Exception;
use SplFixedArray;
/**
 * Encoder.
 */
final class Encoder
{
    /**
     * Default byte encoding.
     */
    public const DEFAULT_BYTE_MODE_ENCODING = 'ISO-8859-1';
    /** @deprecated use DEFAULT_BYTE_MODE_ENCODING */
    public const DEFAULT_BYTE_MODE_ECODING = self::DEFAULT_BYTE_MODE_ENCODING;
    /**
     * Allowed characters for the Alphanumeric Mode.
     */
    private const ALPHANUMERIC_CHARS = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ $%*+-./:';
    /**
     * The original table is defined in the table 5 of JISX0510:2004 (p.19).
     */
    private const ALPHANUMERIC_TABLE = [
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        // 0x00-0x0f
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        // 0x10-0x1f
        36,
        -1,
        -1,
        -1,
        37,
        38,
        -1,
        -1,
        -1,
        -1,
        39,
        40,
        -1,
        41,
        42,
        43,
        // 0x20-0x2f
        0,
        1,
        2,
        3,
        4,
        5,
        6,
        7,
        8,
        9,
        44,
        -1,
        -1,
        -1,
        -1,
        -1,
        // 0x30-0x3f
        -1,
        10,
        11,
        12,
        13,
        14,
        15,
        16,
        17,
        18,
        19,
        20,
        21,
        22,
        23,
        24,
        // 0x40-0x4f
        25,
        26,
        27,
        28,
        29,
        30,
        31,
        32,
        33,
        34,
        35,
        -1,
        -1,
        -1,
        -1,
        -1,
    ];
    /**
     * Codec cache.
     *
     * @var array<string,ReedSolomonCodec>
     */
    private static array $codecs = [];
    /**
     * Encodes "content" with the error correction level "ecLevel".
     */
    public static function encode(
        string $content,
        Error_Correction_Level $ec_level,
        string $encoding = self::DEFAULT_BYTE_MODE_ENCODING,
        ?Version $forced_version = null,
        // Barcode scanner might not be able to read the encoded message of the QR code with the prefix ECI of UTF-8
        bool $prefix_eci = true
    ): Qr_Code
    {
        // Pick an encoding mode appropriate for the content. Note that this
        // will not attempt to use multiple modes / segments even if that were
        // more efficient. Would be nice.
        $mode = self::choose_mode($content, $encoding);
        // This will store the header information, like mode and length, as well
        // as "header" segments like an ECI segment.
        $header_bits = new Bit_Array();
        // Append ECI segment if applicable
        if ($prefix_eci && Mode::BYTE() === $mode && self::DEFAULT_BYTE_MODE_ENCODING !== $encoding) {
            $eci = Character_Set_Eci::get_character_set_eci_by_name($encoding);
            if (null !== $eci) {
                self::append_eci($eci, $header_bits);
            }
        }
        // (With ECI in place,) Write the mode marker
        self::append_mode_info($mode, $header_bits);
        // Collect data within the main segment, separately, to count its size
        // if needed. Don't add it to main payload yet.
        $data_bits = new Bit_Array();
        self::append_bytes($content, $mode, $data_bits, $encoding);
        // Hard part: need to know version to know how many bits length takes.
        // But need to know how many bits it takes to know version. First we
        // take a guess at version by assuming version will be the minimum, 1:
        $provisional_bits_needed = $header_bits->get_size() + $mode->get_character_count_bits(Version::get_version_for_number(1)) + $data_bits->get_size();
        $provisional_version = self::choose_version($provisional_bits_needed, $ec_level);
        // Use that guess to calculate the right version. I am still not sure
        // this works in 100% of cases.
        $bits_needed = $header_bits->get_size() + $mode->get_character_count_bits($provisional_version) + $data_bits->get_size();
        $version = self::choose_version($bits_needed, $ec_level);
        if (null !== $forced_version) {
            // Forced version check
            if ($version->get_version_number() <= $forced_version->get_version_number()) {
                // Calculated minimum version is same or equal as forced version
                $version = $forced_version;
            } else {
                throw new Writer_Exception('Invalid version! Calculated version: ' . $version->get_version_number() . ', requested version: ' . $forced_version->get_version_number());
            }
        }
        $header_and_data_bits = new Bit_Array();
        $header_and_data_bits->append_bit_array($header_bits);
        // Find "length" of main segment and write it.
        $num_letters = match ($mode) {
            Mode::BYTE() => $data_bits->get_size_in_bytes(),
            Mode::NUMERIC(), Mode::ALPHANUMERIC() => strlen($content),
            Mode::KANJI() => iconv_strlen($content, 'utf-8'),
        };
        self::append_length_info($num_letters, $version, $mode, $header_and_data_bits);
        // Put data together into the overall payload.
        $header_and_data_bits->append_bit_array($data_bits);
        $ec_blocks = $version->get_ec_blocks_for_level($ec_level);
        $num_data_bytes = $version->get_total_codewords() - $ec_blocks->get_total_ec_codewords();
        // Terminate the bits properly.
        self::terminate_bits($num_data_bytes, $header_and_data_bits);
        // Interleave data bits with error correction code.
        $final_bits = self::interleave_with_ec_bytes($header_and_data_bits, $version->get_total_codewords(), $num_data_bytes, $ec_blocks->get_num_blocks());
        // Choose the mask pattern.
        $dimension = $version->get_dimension_for_version();
        $matrix = new Byte_Matrix($dimension, $dimension);
        $mask_pattern = self::choose_mask_pattern($final_bits, $ec_level, $version, $matrix);
        // Build the matrix.
        Matrix_Util::build_matrix($final_bits, $ec_level, $version, $mask_pattern, $matrix);
        return new Qr_Code($mode, $ec_level, $version, $mask_pattern, $matrix);
    }
    /**
     * Gets the alphanumeric code for a byte.
     */
    private static function get_alphanumeric_code(int $byte): int
    {
        return self::ALPHANUMERIC_TABLE[$byte] ?? -1;
    }
    /**
     * Chooses the best mode for a given content.
     */
    private static function choose_mode(string $content, ?string $encoding = null): Mode
    {
        if ('' === $content) {
            return Mode::BYTE();
        }
        if (null !== $encoding && 0 === strcasecmp($encoding, 'SHIFT-JIS')) {
            return self::is_only_double_byte_kanji($content) ? Mode::KANJI() : Mode::BYTE();
        }
        if (ctype_digit($content)) {
            return Mode::NUMERIC();
        }
        if (self::is_only_alphanumeric($content)) {
            return Mode::ALPHANUMERIC();
        }
        return Mode::BYTE();
    }
    /**
     * Calculates the mask penalty for a matrix.
     */
    private static function calculate_mask_penalty(Byte_Matrix $matrix): int
    {
        return Mask_Util::apply_mask_penalty_rule1($matrix) + Mask_Util::apply_mask_penalty_rule2($matrix) + Mask_Util::apply_mask_penalty_rule3($matrix) + Mask_Util::apply_mask_penalty_rule4($matrix);
    }
    /**
     * Checks if content only consists of double-byte kanji characters (or is empty).
     */
    private static function is_only_double_byte_kanji(string $content): bool
    {
        $bytes = @iconv('utf-8', 'SHIFT-JIS', $content);
        if (false === $bytes) {
            return false;
        }
        $length = strlen($bytes);
        if (0 !== $length % 2) {
            return false;
        }
        for ($i = 0; $i < $length; $i += 2) {
            $byte = ord($bytes[$i]);
            if (($byte < 0x81 || $byte > 0x9f) && $byte < 0xe0 || $byte > 0xeb) {
                return false;
            }
        }
        return true;
    }
    /**
     * Checks if content only consists of alphanumeric characters (or is empty).
     */
    private static function is_only_alphanumeric(string $content): bool
    {
        return strlen($content) === strspn($content, self::ALPHANUMERIC_CHARS);
    }
    /**
     * Chooses the best mask pattern for a matrix.
     */
    private static function choose_mask_pattern(Bit_Array $bits, Error_Correction_Level $ec_level, Version $version, Byte_Matrix $matrix): int
    {
        $min_penalty = PHP_INT_MAX;
        $best_mask_pattern = -1;
        for ($mask_pattern = 0; $mask_pattern < Qr_Code::NUM_MASK_PATTERNS; ++$mask_pattern) {
            Matrix_Util::build_matrix($bits, $ec_level, $version, $mask_pattern, $matrix);
            $penalty = self::calculate_mask_penalty($matrix);
            if ($penalty < $min_penalty) {
                $min_penalty = $penalty;
                $best_mask_pattern = $mask_pattern;
            }
        }
        return $best_mask_pattern;
    }
    /**
     * Chooses the best version for the input.
     *
     * @throws WriterException if data is too big
     */
    private static function choose_version(int $num_input_bits, Error_Correction_Level $ec_level): Version
    {
        for ($version_num = 1; $version_num <= 40; ++$version_num) {
            $version = Version::get_version_for_number($version_num);
            $num_bytes = $version->get_total_codewords();
            $ec_blocks = $version->get_ec_blocks_for_level($ec_level);
            $num_ec_bytes = $ec_blocks->get_total_ec_codewords();
            $num_data_bytes = $num_bytes - $num_ec_bytes;
            $total_input_bytes = intdiv($num_input_bits + 8, 8);
            if ($num_data_bytes >= $total_input_bytes) {
                return $version;
            }
        }
        throw new Writer_Exception('Data too big');
    }
    /**
     * Terminates the bits in a bit array.
     *
     * @throws WriterException if data bits cannot fit in the QR code
     * @throws WriterException if bits size does not equal the capacity
     */
    private static function terminate_bits(int $num_data_bytes, Bit_Array $bits): void
    {
        $capacity = $num_data_bytes << 3;
        if ($bits->get_size() > $capacity) {
            throw new Writer_Exception('Data bits cannot fit in the QR code');
        }
        for ($i = 0; $i < 4 && $bits->get_size() < $capacity; ++$i) {
            $bits->append_bit(false);
        }
        $num_bits_in_last_byte = $bits->get_size() & 0x7;
        if ($num_bits_in_last_byte > 0) {
            for ($i = $num_bits_in_last_byte; $i < 8; ++$i) {
                $bits->append_bit(false);
            }
        }
        $num_padding_bytes = $num_data_bytes - $bits->get_size_in_bytes();
        for ($i = 0; $i < $num_padding_bytes; ++$i) {
            $bits->append_bits(0 === ($i & 0x1) ? 0xec : 0x11, 8);
        }
        if ($bits->get_size() !== $capacity) {
            throw new Writer_Exception('Bits size does not equal capacity');
        }
    }
    /**
     * Gets number of data- and EC bytes for a block ID.
     *
     * @return int[]
     * @throws WriterException if block ID is too large
     * @throws WriterException if EC bytes mismatch
     * @throws WriterException if RS blocks mismatch
     * @throws WriterException if total bytes mismatch
     */
    private static function get_num_data_bytes_and_num_ec_bytes_for_block_id(int $num_total_bytes, int $num_data_bytes, int $num_rs_blocks, int $block_id): array
    {
        if ($block_id >= $num_rs_blocks) {
            throw new Writer_Exception('Block ID too large');
        }
        $num_rs_blocks_in_group2 = $num_total_bytes % $num_rs_blocks;
        $num_rs_blocks_in_group1 = $num_rs_blocks - $num_rs_blocks_in_group2;
        $num_total_bytes_in_group1 = intdiv($num_total_bytes, $num_rs_blocks);
        $num_total_bytes_in_group2 = $num_total_bytes_in_group1 + 1;
        $num_data_bytes_in_group1 = intdiv($num_data_bytes, $num_rs_blocks);
        $num_data_bytes_in_group2 = $num_data_bytes_in_group1 + 1;
        $num_ec_bytes_in_group1 = $num_total_bytes_in_group1 - $num_data_bytes_in_group1;
        $num_ec_bytes_in_group2 = $num_total_bytes_in_group2 - $num_data_bytes_in_group2;
        if ($num_ec_bytes_in_group1 !== $num_ec_bytes_in_group2) {
            throw new Writer_Exception('EC bytes mismatch');
        }
        if ($num_rs_blocks !== $num_rs_blocks_in_group1 + $num_rs_blocks_in_group2) {
            throw new Writer_Exception('RS blocks mismatch');
        }
        if ($num_total_bytes !== ($num_data_bytes_in_group1 + $num_ec_bytes_in_group1) * $num_rs_blocks_in_group1 + ($num_data_bytes_in_group2 + $num_ec_bytes_in_group2) * $num_rs_blocks_in_group2) {
            throw new Writer_Exception('Total bytes mismatch');
        }
        if ($block_id < $num_rs_blocks_in_group1) {
            return [$num_data_bytes_in_group1, $num_ec_bytes_in_group1];
        }
        return [$num_data_bytes_in_group2, $num_ec_bytes_in_group2];
    }
    /**
     * Interleaves data with EC bytes.
     *
     * @throws WriterException if number of bits and data bytes does not match
     * @throws WriterException if data bytes does not match offset
     * @throws WriterException if an interleaving error occurs
     */
    private static function interleave_with_ec_bytes(Bit_Array $bits, int $num_total_bytes, int $num_data_bytes, int $num_rs_blocks): Bit_Array
    {
        if ($bits->get_size_in_bytes() !== $num_data_bytes) {
            throw new Writer_Exception('Number of bits and data bytes does not match');
        }
        $data_bytes_offset = 0;
        $max_num_data_bytes = 0;
        $max_num_ec_bytes = 0;
        $blocks = new SplFixedArray($num_rs_blocks);
        for ($i = 0; $i < $num_rs_blocks; ++$i) {
            [$num_data_bytes_in_block, $num_ec_bytes_in_block] = self::get_num_data_bytes_and_num_ec_bytes_for_block_id($num_total_bytes, $num_data_bytes, $num_rs_blocks, $i);
            $size = $num_data_bytes_in_block;
            $data_bytes = $bits->to_bytes(8 * $data_bytes_offset, $size);
            $ec_bytes = self::generate_ec_bytes($data_bytes, $num_ec_bytes_in_block);
            $blocks[$i] = new Block_Pair($data_bytes, $ec_bytes);
            $max_num_data_bytes = max($max_num_data_bytes, $size);
            $max_num_ec_bytes = max($max_num_ec_bytes, count($ec_bytes));
            $data_bytes_offset += $num_data_bytes_in_block;
        }
        if ($num_data_bytes !== $data_bytes_offset) {
            throw new Writer_Exception('Data bytes does not match offset');
        }
        $result = new Bit_Array();
        for ($i = 0; $i < $max_num_data_bytes; ++$i) {
            foreach ($blocks as $block) {
                $data_bytes = $block->get_data_bytes();
                if ($i < count($data_bytes)) {
                    $result->append_bits($data_bytes[$i], 8);
                }
            }
        }
        for ($i = 0; $i < $max_num_ec_bytes; ++$i) {
            foreach ($blocks as $block) {
                $ec_bytes = $block->get_error_correction_bytes();
                if ($i < count($ec_bytes)) {
                    $result->append_bits($ec_bytes[$i], 8);
                }
            }
        }
        if ($num_total_bytes !== $result->get_size_in_bytes()) {
            throw new Writer_Exception('Interleaving error: ' . $num_total_bytes . ' and ' . $result->get_size_in_bytes() . ' differ');
        }
        return $result;
    }
    /**
     * Generates EC bytes for given data.
     *
     * @param  SplFixedArray<int> $dataBytes
     * @return SplFixedArray<int>
     */
    private static function generate_ec_bytes(SplFixedArray $data_bytes, int $num_ec_bytes_in_block): SplFixedArray
    {
        $num_data_bytes = count($data_bytes);
        $to_encode = new SplFixedArray($num_data_bytes + $num_ec_bytes_in_block);
        for ($i = 0; $i < $num_data_bytes; $i++) {
            $to_encode[$i] = $data_bytes[$i];
        }
        $ec_bytes = new SplFixedArray($num_ec_bytes_in_block);
        $codec = self::get_codec($num_data_bytes, $num_ec_bytes_in_block);
        $codec->encode($to_encode, $ec_bytes);
        return $ec_bytes;
    }
    /**
     * Gets an RS codec and caches it.
     */
    private static function get_codec(int $num_data_bytes, int $num_ec_bytes_in_block): Reed_Solomon_Codec
    {
        $cache_id = $num_data_bytes . '-' . $num_ec_bytes_in_block;
        return self::$codecs[$cache_id] ?? self::$codecs[$cache_id] = new Reed_Solomon_Codec(8, 0x11d, 0, 1, $num_ec_bytes_in_block, 255 - $num_data_bytes - $num_ec_bytes_in_block);
    }
    /**
     * Appends mode information to a bit array.
     */
    private static function append_mode_info(Mode $mode, Bit_Array $bits): void
    {
        $bits->append_bits($mode->get_bits(), 4);
    }
    /**
     * Appends length information to a bit array.
     *
     * @throws WriterException if num letters is bigger than expected
     */
    private static function append_length_info(int $num_letters, Version $version, Mode $mode, Bit_Array $bits): void
    {
        $num_bits = $mode->get_character_count_bits($version);
        if ($num_letters >= 1 << $num_bits) {
            throw new Writer_Exception($num_letters . ' is bigger than ' . ((1 << $num_bits) - 1));
        }
        $bits->append_bits($num_letters, $num_bits);
    }
    /**
     * Appends bytes to a bit array in a specific mode.
     */
    private static function append_bytes(string $content, Mode $mode, Bit_Array $bits, string $encoding): void
    {
        match ($mode) {
            Mode::NUMERIC() => self::append_numeric_bytes($content, $bits),
            Mode::ALPHANUMERIC() => self::append_alphanumeric_bytes($content, $bits),
            Mode::BYTE() => self::append8bit_bytes($content, $bits, $encoding),
            Mode::KANJI() => self::append_kanji_bytes($content, $bits),
        };
    }
    /**
     * Appends numeric bytes to a bit array.
     */
    private static function append_numeric_bytes(string $content, Bit_Array $bits): void
    {
        $length = strlen($content);
        $i = 0;
        while ($i < $length) {
            $num1 = (int) $content[$i];
            if ($i + 2 < $length) {
                // Encode three numeric letters in ten bits.
                $num2 = (int) $content[$i + 1];
                $num3 = (int) $content[$i + 2];
                $bits->append_bits($num1 * 100 + $num2 * 10 + $num3, 10);
                $i += 3;
            } elseif ($i + 1 < $length) {
                // Encode two numeric letters in seven bits.
                $num2 = (int) $content[$i + 1];
                $bits->append_bits($num1 * 10 + $num2, 7);
                $i += 2;
            } else {
                // Encode one numeric letter in four bits.
                $bits->append_bits($num1, 4);
                ++$i;
            }
        }
    }
    /**
     * Appends alpha-numeric bytes to a bit array.
     *
     * @throws WriterException if an invalid alphanumeric code was found
     */
    private static function append_alphanumeric_bytes(string $content, Bit_Array $bits): void
    {
        $length = strlen($content);
        $i = 0;
        while ($i < $length) {
            $code1 = self::get_alphanumeric_code(ord($content[$i]));
            if (-1 === $code1) {
                throw new Writer_Exception('Invalid alphanumeric code');
            }
            if ($i + 1 < $length) {
                $code2 = self::get_alphanumeric_code(ord($content[$i + 1]));
                if (-1 === $code2) {
                    throw new Writer_Exception('Invalid alphanumeric code');
                }
                // Encode two alphanumeric letters in 11 bits.
                $bits->append_bits($code1 * 45 + $code2, 11);
                $i += 2;
            } else {
                // Encode one alphanumeric letter in six bits.
                $bits->append_bits($code1, 6);
                ++$i;
            }
        }
    }
    /**
     * Appends regular 8-bit bytes to a bit array.
     *
     * @throws WriterException if content cannot be encoded to target encoding
     */
    private static function append8bit_bytes(string $content, Bit_Array $bits, string $encoding): void
    {
        $bytes = @iconv('utf-8', $encoding, $content);
        if (false === $bytes) {
            throw new Writer_Exception('Could not encode content to ' . $encoding);
        }
        $length = strlen($bytes);
        for ($i = 0; $i < $length; $i++) {
            $bits->append_bits(ord($bytes[$i]), 8);
        }
    }
    /**
     * Appends KANJI bytes to a bit array.
     *
     * @throws WriterException if content does not seem to be encoded in SHIFT-JIS
     * @throws WriterException if an invalid byte sequence occurs
     */
    private static function append_kanji_bytes(string $content, Bit_Array $bits): void
    {
        $bytes = @iconv('utf-8', 'SHIFT-JIS', $content);
        if (false === $bytes) {
            throw new Writer_Exception('Content could not be converted to SHIFT-JIS');
        }
        if (strlen($bytes) % 2 > 0) {
            // We just do a simple length check here. The for loop will check
            // individual characters.
            throw new Writer_Exception('Content does not seem to be encoded in SHIFT-JIS');
        }
        $length = strlen($bytes);
        for ($i = 0; $i < $length; $i += 2) {
            $byte1 = ord($bytes[$i]);
            $byte2 = ord($bytes[$i + 1]);
            $code = $byte1 << 8 | $byte2;
            if ($code >= 0x8140 && $code <= 0x9ffc) {
                $subtracted = $code - 0x8140;
            } elseif ($code >= 0xe040 && $code <= 0xebbf) {
                $subtracted = $code - 0xc140;
            } else {
                throw new Writer_Exception('Invalid byte sequence');
            }
            $encoded = ($subtracted >> 8) * 0xc0 + ($subtracted & 0xff);
            $bits->append_bits($encoded, 13);
        }
    }
    /**
     * Appends ECI information to a bit array.
     */
    private static function append_eci(Character_Set_Eci $eci, Bit_Array $bits): void
    {
        $mode = Mode::ECI();
        $bits->append_bits($mode->get_bits(), 4);
        $bits->append_bits($eci->get_value(), 8);
    }
}