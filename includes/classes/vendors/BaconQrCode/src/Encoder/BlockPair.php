<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Encoder;

use SplFixedArray;
/**
 * Block pair.
 */
final readonly class Block_Pair
{
    /**
     * Creates a new block pair.
     *
     * @param SplFixedArray<int> $dataBytes Data bytes in the block.
     * @param SplFixedArray<int> $errorCorrectionBytes Error correction bytes in the block.
     */
    public function __construct(private SplFixedArray $data_bytes, private SplFixedArray $error_correction_bytes)
    {
    }
    /**
     * Gets the data bytes.
     *
     * @return SplFixedArray<int>
     */
    public function get_data_bytes(): SplFixedArray
    {
        return $this->data_bytes;
    }
    /**
     * Gets the error correction bytes.
     *
     * @return SplFixedArray<int>
     */
    public function get_error_correction_bytes(): SplFixedArray
    {
        return $this->error_correction_bytes;
    }
}