<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Common;

use Bacon_Qr_Code\Exception\InvalidArgumentException;
use Bacon_Qr_Code\Exception\RuntimeException;
use SplFixedArray;
/**
 * Reed-Solomon codec for 8-bit characters.
 *
 * Based on libfec by Phil Karn, KA9Q.
 */
final class Reed_Solomon_Codec
{
    /**
     * Symbol size in bits.
     */
    private readonly int $symbol_size;
    /**
     * Block size in symbols.
     */
    private readonly int $block_size;
    /**
     * First root of RS code generator polynomial, index form.
     */
    private readonly int $first_root;
    /**
     * Prim-th root of 1, index form.
     */
    private readonly int $i_primitive;
    /**
     * RS code generator polynomial degree (number of roots).
     */
    private readonly int $num_roots;
    /**
     * Padding bytes at front of shortened block.
     */
    private readonly int $padding;
    /**
     * Log lookup table.
     */
    private SplFixedArray $alpha_to;
    /**
     * Anti-Log lookup table.
     */
    private SplFixedArray $index_of;
    /**
     * Generator polynomial.
     */
    private SplFixedArray $generator_poly;
    /**
     * @throws InvalidArgumentException if symbol size ist not between 0 and 8
     * @throws InvalidArgumentException if first root is invalid
     * @throws InvalidArgumentException if num roots is invalid
     * @throws InvalidArgumentException if padding is invalid
     * @throws RuntimeException if field generator polynomial is not primitive
     */
    public function __construct(
        int $symbol_size,
        int $gf_poly,
        int $first_root,
        /**
         * Primitive element to generate polynomial roots, index form.
         */
        private readonly int $primitive,
        int $num_roots,
        int $padding
    )
    {
        if ($symbol_size < 0 || $symbol_size > 8) {
            throw new InvalidArgumentException('Symbol size must be between 0 and 8');
        }
        if ($first_root < 0 || $first_root >= 1 << $symbol_size) {
            throw new InvalidArgumentException('First root must be between 0 and ' . (1 << $symbol_size));
        }
        if ($num_roots < 0 || $num_roots >= 1 << $symbol_size) {
            throw new InvalidArgumentException('Num roots must be between 0 and ' . (1 << $symbol_size));
        }
        if ($padding < 0 || $padding >= (1 << $symbol_size) - 1 - $num_roots) {
            throw new InvalidArgumentException('Padding must be between 0 and ' . ((1 << $symbol_size) - 1 - $num_roots));
        }
        $this->symbol_size = $symbol_size;
        $this->block_size = (1 << $symbol_size) - 1;
        $this->padding = $padding;
        $this->alpha_to = SplFixedArray::from_array(array_fill(0, $this->block_size + 1, 0), false);
        $this->index_of = SplFixedArray::from_array(array_fill(0, $this->block_size + 1, 0), false);
        // Generate galous field lookup table
        $this->index_of[0] = $this->block_size;
        $this->alpha_to[$this->block_size] = 0;
        $sr = 1;
        for ($i = 0; $i < $this->block_size; ++$i) {
            $this->index_of[$sr] = $i;
            $this->alpha_to[$i] = $sr;
            $sr <<= 1;
            if ($sr & 1 << $symbol_size) {
                $sr ^= $gf_poly;
            }
            $sr &= $this->block_size;
        }
        if (1 !== $sr) {
            throw new RuntimeException('Field generator polynomial is not primitive');
        }
        // Form RS code generator polynomial from its roots
        $this->generator_poly = SplFixedArray::from_array(array_fill(0, $num_roots + 1, 0), false);
        $this->first_root = $first_root;
        $this->num_roots = $num_roots;
        $this->i_primitive = intdiv($i_primitive, $this->primitive);
        $this->generator_poly[0] = 1;
        for ($i = 0, $root = $first_root * $this->primitive; $i < $num_roots; ++$i, $root += $this->primitive) {
            $this->generator_poly[$i + 1] = 1;
            for ($j = $i; $j > 0; $j--) {
                if ($this->generator_poly[$j] !== 0) {
                    $this->generator_poly[$j] = $this->generator_poly[$j - 1] ^ $this->alpha_to[$this->mod_nn($this->index_of[$this->generator_poly[$j]] + $root)];
                } else {
                    $this->generator_poly[$j] = $this->generator_poly[$j - 1];
                }
            }
            $this->generator_poly[$j] = $this->alpha_to[$this->mod_nn($this->index_of[$this->generator_poly[0]] + $root)];
        }
        // Convert generator poly to index form for quicker encoding
        for ($i = 0; $i <= $num_roots; ++$i) {
            $this->generator_poly[$i] = $this->index_of[$this->generator_poly[$i]];
        }
    }
    /**
     * Encodes data and writes result back into parity array.
     */
    public function encode(SplFixedArray $data, SplFixedArray $parity): void
    {
        for ($i = 0; $i < $this->num_roots; ++$i) {
            $parity[$i] = 0;
        }
        $iterations = $this->block_size - $this->num_roots - $this->padding;
        for ($i = 0; $i < $iterations; ++$i) {
            $feedback = $this->index_of[$data[$i] ^ $parity[0]];
            if ($feedback !== $this->block_size) {
                // Feedback term is non-zero
                $feedback = $this->mod_nn($this->block_size - $this->generator_poly[$this->num_roots] + $feedback);
                for ($j = 1; $j < $this->num_roots; ++$j) {
                    $parity[$j] = $parity[$j] ^ $this->alpha_to[$this->mod_nn($feedback + $this->generator_poly[$this->num_roots - $j])];
                }
            }
            for ($j = 0; $j < $this->num_roots - 1; ++$j) {
                $parity[$j] = $parity[$j + 1];
            }
            if ($feedback !== $this->block_size) {
                $parity[$this->num_roots - 1] = $this->alpha_to[$this->mod_nn($feedback + $this->generator_poly[0])];
            } else {
                $parity[$this->num_roots - 1] = 0;
            }
        }
    }
    /**
     * Decodes received data.
     */
    public function decode(SplFixedArray $data, ?SplFixedArray $erasures = null): ?int
    {
        // This speeds up the initialization a bit.
        $num_roots_plus_one = SplFixedArray::from_array(array_fill(0, $this->num_roots + 1, 0), false);
        $num_roots = SplFixedArray::from_array(array_fill(0, $this->num_roots, 0), false);
        $lambda = clone $num_roots_plus_one;
        $b = clone $num_roots_plus_one;
        $t = clone $num_roots_plus_one;
        $omega = clone $num_roots_plus_one;
        $root = clone $num_roots;
        $loc = clone $num_roots;
        $num_erasures = null !== $erasures ? count($erasures) : 0;
        // Form the Syndromes; i.e., evaluate data(x) at roots of g(x)
        $syndromes = SplFixedArray::from_array(array_fill(0, $this->num_roots, $data[0]), false);
        for ($i = 1; $i < $this->block_size - $this->padding; ++$i) {
            for ($j = 0; $j < $this->num_roots; ++$j) {
                if ($syndromes[$j] === 0) {
                    $syndromes[$j] = $data[$i];
                } else {
                    $syndromes[$j] = $data[$i] ^ $this->alpha_to[$this->mod_nn($this->index_of[$syndromes[$j]] + ($this->first_root + $j) * $this->primitive)];
                }
            }
        }
        // Convert syndromes to index form, checking for nonzero conditions
        $syndrome_error = 0;
        for ($i = 0; $i < $this->num_roots; ++$i) {
            $syndrome_error |= $syndromes[$i];
            $syndromes[$i] = $this->index_of[$syndromes[$i]];
        }
        if (!$syndrome_error) {
            // If syndrome is zero, data[] is a codeword and there are no errors to correct, so return data[]
            // unmodified.
            return 0;
        }
        $lambda[0] = 1;
        if ($num_erasures > 0) {
            // Init lambda to be the erasure locator polynomial
            $lambda[1] = $this->alpha_to[$this->mod_nn($this->primitive * ($this->block_size - 1 - $erasures[0]))];
            for ($i = 1; $i < $num_erasures; ++$i) {
                $u = $this->mod_nn($this->primitive * ($this->block_size - 1 - $erasures[$i]));
                for ($j = $i + 1; $j > 0; --$j) {
                    $tmp = $this->index_of[$lambda[$j - 1]];
                    if ($tmp !== $this->block_size) {
                        $lambda[$j] = $lambda[$j] ^ $this->alpha_to[$this->mod_nn($u + $tmp)];
                    }
                }
            }
        }
        for ($i = 0; $i <= $this->num_roots; ++$i) {
            $b[$i] = $this->index_of[$lambda[$i]];
        }
        // Begin Berlekamp-Massey algorithm to determine error+erasure locator polynomial
        $r = $num_erasures;
        $el = $num_erasures;
        while (++$r <= $this->num_roots) {
            // Compute discrepancy at the r-th step in poly form
            $discrepancy_r = 0;
            for ($i = 0; $i < $r; ++$i) {
                if ($lambda[$i] !== 0 && $syndromes[$r - $i - 1] !== $this->block_size) {
                    $discrepancy_r ^= $this->alpha_to[$this->mod_nn($this->index_of[$lambda[$i]] + $syndromes[$r - $i - 1])];
                }
            }
            $discrepancy_r = $this->index_of[$discrepancy_r];
            if ($discrepancy_r === $this->block_size) {
                $tmp = $b->to_array();
                array_unshift($tmp, $this->block_size);
                array_pop($tmp);
                $b = SplFixedArray::from_array($tmp, false);
                continue;
            }
            $t[0] = $lambda[0];
            for ($i = 0; $i < $this->num_roots; ++$i) {
                if ($b[$i] !== $this->block_size) {
                    $t[$i + 1] = $lambda[$i + 1] ^ $this->alpha_to[$this->mod_nn($discrepancy_r + $b[$i])];
                } else {
                    $t[$i + 1] = $lambda[$i + 1];
                }
            }
            if (2 * $el <= $r + $num_erasures - 1) {
                $el = $r + $num_erasures - $el;
                for ($i = 0; $i <= $this->num_roots; ++$i) {
                    $b[$i] = $lambda[$i] === 0 ? $this->block_size : $this->mod_nn($this->index_of[$lambda[$i]] - $discrepancy_r + $this->block_size);
                }
            } else {
                $tmp = $b->to_array();
                array_unshift($tmp, $this->block_size);
                array_pop($tmp);
                $b = SplFixedArray::from_array($tmp, false);
            }
            $lambda = clone $t;
        }
        // Convert lambda to index form and compute deg(lambda(x))
        $deg_lambda = 0;
        for ($i = 0; $i <= $this->num_roots; ++$i) {
            $lambda[$i] = $this->index_of[$lambda[$i]];
            if ($lambda[$i] !== $this->block_size) {
                $deg_lambda = $i;
            }
        }
        // Find roots of the error+erasure locator polynomial by Chien search.
        $reg = clone $lambda;
        $reg[0] = 0;
        $count = 0;
        $i = 1;
        for ($k = $this->i_primitive - 1; $i <= $this->block_size; ++$i, $k = $this->mod_nn($k + $this->i_primitive)) {
            $q = 1;
            for ($j = $deg_lambda; $j > 0; $j--) {
                if ($reg[$j] !== $this->block_size) {
                    $reg[$j] = $this->mod_nn($reg[$j] + $j);
                    $q ^= $this->alpha_to[$reg[$j]];
                }
            }
            if ($q !== 0) {
                // Not a root
                continue;
            }
            // Store root (index-form) and error location number
            $root[$count] = $i;
            $loc[$count] = $k;
            if (++$count === $deg_lambda) {
                break;
            }
        }
        if ($deg_lambda !== $count) {
            // deg(lambda) unequal to number of roots: uncorrectable error detected
            return null;
        }
        // Compute err+eras evaluate poly omega(x) = s(x)*lambda(x) (modulo x**numRoots). In index form. Also find
        // deg(omega).
        $deg_omega = $deg_lambda - 1;
        for ($i = 0; $i <= $deg_omega; ++$i) {
            $tmp = 0;
            for ($j = $i; $j >= 0; --$j) {
                if ($syndromes[$i - $j] !== $this->block_size && $lambda[$j] !== $this->block_size) {
                    $tmp ^= $this->alpha_to[$this->mod_nn($syndromes[$i - $j] + $lambda[$j])];
                }
            }
            $omega[$i] = $this->index_of[$tmp];
        }
        // Compute error values in poly-form. num1 = omega(inv(X(l))), num2 = inv(X(l))**(firstRoot-1) and
        // den = lambda_pr(inv(X(l))) all in poly form.
        for ($j = $count - 1; $j >= 0; --$j) {
            $num1 = 0;
            for ($i = $deg_omega; $i >= 0; $i--) {
                if ($omega[$i] !== $this->block_size) {
                    $num1 ^= $this->alpha_to[$this->mod_nn($omega[$i] + $i * $root[$j])];
                }
            }
            $num2 = $this->alpha_to[$this->mod_nn($root[$j] * ($this->first_root - 1) + $this->block_size)];
            $den = 0;
            // lambda[i+1] for i even is the formal derivativelambda_pr of lambda[i]
            for ($i = min($deg_lambda, $this->num_roots - 1) & ~1; $i >= 0; $i -= 2) {
                if ($lambda[$i + 1] !== $this->block_size) {
                    $den ^= $this->alpha_to[$this->mod_nn($lambda[$i + 1] + $i * $root[$j])];
                }
            }
            // Apply error to data
            if ($num1 !== 0 && $loc[$j] >= $this->padding) {
                $data[$loc[$j] - $this->padding] = $data[$loc[$j] - $this->padding] ^ $this->alpha_to[$this->mod_nn($this->index_of[$num1] + $this->index_of[$num2] + $this->block_size - $this->index_of[$den])];
            }
        }
        if (null !== $erasures) {
            if (count($erasures) < $count) {
                $erasures->set_size($count);
            }
            for ($i = 0; $i < $count; $i++) {
                $erasures[$i] = $loc[$i];
            }
        }
        return $count;
    }
    /**
     * Computes $x % GF_SIZE, where GF_SIZE is 2**GF_BITS - 1, without a slow divide.
     */
    private function mod_nn(int $x): int
    {
        while ($x >= $this->block_size) {
            $x -= $this->block_size;
            $x = ($x >> $this->symbol_size) + ($x & $this->block_size);
        }
        return $x;
    }
}