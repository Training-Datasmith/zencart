<?php

declare(strict_types=1);

namespace BaconQrCode\Encoder;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Common\Mode;
use BaconQrCode\Common\Version;

/**
 * QR code.
 */
final readonly class QrCode implements \Stringable
{
    /**
     * Number of possible mask patterns.
     */
    public const NUM_MASK_PATTERNS = 8;

    public function __construct(
        private Mode                 $mode,
        private ErrorCorrectionLevel $errorCorrectionLevel,
        private Version              $version,
        /**
         * Mask pattern of the QR code.
         */
        private int                                   $maskPattern,
        /**
         * Matrix of the QR code.
         */
        private ByteMatrix                            $matrix
    ) {
    }

    /**
     * Gets the mode.
     */
    public function getMode(): Mode
    {
        return $this->mode;
    }

    /**
     * Gets the EC level.
     */
    public function getErrorCorrectionLevel(): ErrorCorrectionLevel
    {
        return $this->errorCorrectionLevel;
    }

    /**
     * Gets the version.
     */
    public function getVersion(): Version
    {
        return $this->version;
    }

    /**
     * Gets the mask pattern.
     */
    public function getMaskPattern(): int
    {
        return $this->maskPattern;
    }

    public function getMatrix(): ByteMatrix
    {
        return $this->matrix;
    }

    /**
     * Validates whether a mask pattern is valid.
     */
    public static function isValidMaskPattern(int $maskPattern): bool
    {
        return $maskPattern > 0 && $maskPattern < self::NUM_MASK_PATTERNS;
    }

    /**
     * Returns a string representation of the QR code.
     */
    public function __toString(): string
    {
        $result = "<<\n"
                . ' mode: ' . $this->mode . "\n"
                . ' ecLevel: ' . $this->errorCorrectionLevel . "\n"
                . ' version: ' . $this->version . "\n"
                . ' maskPattern: ' . $this->maskPattern . "\n";

        if ($this->matrix === null) {
            $result .= " matrix: null\n";
        } else {
            $result .= " matrix:\n";
            $result .= $this->matrix;
        }

        return $result . ">>\n";
    }
}
