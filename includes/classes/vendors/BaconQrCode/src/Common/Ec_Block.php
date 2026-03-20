<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Common;

/**
 * Encapsulates the parameters for one error-correction block in one symbol version.
 *
 * This includes the number of data codewords, and the number of times a block with these parameters is used
 * consecutively in the QR code version's format.
 */
final readonly class Ec_Block
{
    public function __construct(private int $count, private int $data_codewords)
    {
    }
    /**
     * Returns how many times the block is used.
     */
    public function get_count(): int
    {
        return $this->count;
    }
    /**
     * Returns the number of data codewords.
     */
    public function get_data_codewords(): int
    {
        return $this->data_codewords;
    }
}