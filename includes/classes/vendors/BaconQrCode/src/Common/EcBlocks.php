<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Common;

/**
 * Encapsulates a set of error-correction blocks in one symbol version.
 *
 * Most versions will use blocks of differing sizes within one version, so, this encapsulates the parameters for each
 * set of blocks. It also holds the number of error-correction codewords per block since it will be the same across all
 * blocks within one version.
 */
final readonly class Ec_Blocks
{
    /**
     * List of EC blocks.
     *
     * @var EcBlock[]
     */
    private array $ec_blocks;
    public function __construct(private int $ec_codewords_per_block, Ec_Block ...$ec_blocks)
    {
        $this->ec_blocks = $ec_blocks;
    }
    /**
     * Returns the number of EC codewords per block.
     */
    public function get_ec_codewords_per_block(): int
    {
        return $this->ec_codewords_per_block;
    }
    /**
     * Returns the total number of EC block appearances.
     */
    public function get_num_blocks(): int
    {
        $total = 0;
        foreach ($this->ec_blocks as $ec_block) {
            $total += $ec_block->get_count();
        }
        return $total;
    }
    /**
     * Returns the total count of EC codewords.
     */
    public function get_total_ec_codewords(): int
    {
        return $this->ec_codewords_per_block * $this->get_num_blocks();
    }
    /**
     * Returns the EC blocks included in this collection.
     *
     * @return EcBlock[]
     */
    public function get_ec_blocks(): array
    {
        return $this->ec_blocks;
    }
}