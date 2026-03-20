<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Common;

use Bacon_Qr_Code\Exception\OutOfBoundsException;
use Dasp_Ri_D\Enum\Abstract_Enum;
/**
 * Enum representing the four error correction levels.
 *
 * @method static self L() ~7% correction
 * @method static self M() ~15% correction
 * @method static self Q() ~25% correction
 * @method static self H() ~30% correction
 */
final class Error_Correction_Level extends Abstract_Enum
{
    protected const L = [0x1];
    protected const M = [0x0];
    protected const Q = [0x3];
    protected const H = [0x2];
    protected function __construct(private readonly int $bits)
    {
    }
    /**
     * @throws OutOfBoundsException if number of bits is invalid
     */
    public static function for_bits(int $bits): self
    {
        return match ($bits) {
            0 => self::M(),
            1 => self::L(),
            2 => self::H(),
            3 => self::Q(),
            default => throw new OutOfBoundsException('Invalid number of bits'),
        };
    }
    /**
     * Returns the two bits used to encode this error correction level.
     */
    public function get_bits(): int
    {
        return $this->bits;
    }
}