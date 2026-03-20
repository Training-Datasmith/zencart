<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Encoder;

use Bacon_Qr_Code\Common\Error_Correction_Level;
use Bacon_Qr_Code\Common\Mode;
use Bacon_Qr_Code\Common\Version;
/**
 * QR code.
 */
final readonly class Qr_Code implements \Stringable
{
    /**
     * Number of possible mask patterns.
     */
    public const NUM_MASK_PATTERNS = 8;
    public function __construct(
        private Mode $mode,
        private Error_Correction_Level $error_correction_level,
        private Version $version,
        /**
         * Mask pattern of the QR code.
         */
        private int $mask_pattern,
        /**
         * Matrix of the QR code.
         */
        private Byte_Matrix $matrix
    )
    {
    }
    /**
     * Gets the mode.
     */
    public function get_mode(): Mode
    {
        return $this->mode;
    }
    /**
     * Gets the EC level.
     */
    public function get_error_correction_level(): Error_Correction_Level
    {
        return $this->error_correction_level;
    }
    /**
     * Gets the version.
     */
    public function get_version(): Version
    {
        return $this->version;
    }
    /**
     * Gets the mask pattern.
     */
    public function get_mask_pattern(): int
    {
        return $this->mask_pattern;
    }
    public function get_matrix(): Byte_Matrix
    {
        return $this->matrix;
    }
    /**
     * Validates whether a mask pattern is valid.
     */
    public static function is_valid_mask_pattern(int $mask_pattern): bool
    {
        return $mask_pattern > 0 && $mask_pattern < self::NUM_MASK_PATTERNS;
    }
    /**
     * Returns a string representation of the QR code.
     */
    public function __toString(): string
    {
        $result = "<<\n" . ' mode: ' . $this->mode . "\n" . ' ecLevel: ' . $this->error_correction_level . "\n" . ' version: ' . $this->version . "\n" . ' maskPattern: ' . $this->mask_pattern . "\n";
        if ($this->matrix === null) {
            $result .= " matrix: null\n";
        } else {
            $result .= " matrix:\n";
            $result .= $this->matrix;
        }
        return $result . ">>\n";
    }
}