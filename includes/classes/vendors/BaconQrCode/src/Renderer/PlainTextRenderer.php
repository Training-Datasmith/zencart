<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer;

use Bacon_Qr_Code\Encoder\Qr_Code;
use Bacon_Qr_Code\Exception\InvalidArgumentException;
final readonly class Plain_Text_Renderer implements Renderer_Interface
{
    /**
     * UTF-8 full block (U+2588)
     */
    private const FULL_BLOCK = "█";
    /**
     * UTF-8 upper half block (U+2580)
     */
    private const UPPER_HALF_BLOCK = "▀";
    /**
     * UTF-8 lower half block (U+2584)
     */
    private const LOWER_HALF_BLOCK = "▄";
    /**
     * UTF-8 no-break space (U+00A0)
     */
    private const EMPTY_BLOCK = " ";
    public function __construct(private int $margin = 2)
    {
    }
    /**
     * @throws InvalidArgumentException if matrix width doesn't match height
     */
    public function render(Qr_Code $qr_code): string
    {
        $matrix = $qr_code->get_matrix();
        $matrix_size = $matrix->get_width();
        if ($matrix_size !== $matrix->get_height()) {
            throw new InvalidArgumentException('Matrix must have the same width and height');
        }
        $rows = $matrix->get_array()->to_array();
        if (0 !== $matrix_size % 2) {
            $rows[] = array_fill(0, $matrix_size, 0);
        }
        $horizontal_margin = str_repeat(self::EMPTY_BLOCK, $this->margin);
        $result = str_repeat("\n", (int) ceil($this->margin / 2));
        for ($i = 0; $i < $matrix_size; $i += 2) {
            $result .= $horizontal_margin;
            $upper_row = $rows[$i];
            $lower_row = $rows[$i + 1];
            for ($j = 0; $j < $matrix_size; ++$j) {
                $upper_bit = $upper_row[$j];
                $lower_bit = $lower_row[$j];
                if ($upper_bit) {
                    $result .= $lower_bit ? self::FULL_BLOCK : self::UPPER_HALF_BLOCK;
                } else {
                    $result .= $lower_bit ? self::LOWER_HALF_BLOCK : self::EMPTY_BLOCK;
                }
            }
            $result .= $horizontal_margin . "\n";
        }
        return $result . str_repeat("\n", (int) ceil($this->margin / 2));
    }
}