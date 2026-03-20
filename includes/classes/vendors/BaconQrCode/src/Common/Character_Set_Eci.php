<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Common;

use Bacon_Qr_Code\Exception\InvalidArgumentException;
use Dasp_Ri_D\Enum\Abstract_Enum;
/**
 * Encapsulates a Character Set ECI, according to "Extended Channel Interpretations" 5.3.1.1 of ISO 18004.
 *
 * @method static self CP437()
 * @method static self ISO8859_1()
 * @method static self ISO8859_2()
 * @method static self ISO8859_3()
 * @method static self ISO8859_4()
 * @method static self ISO8859_5()
 * @method static self ISO8859_6()
 * @method static self ISO8859_7()
 * @method static self ISO8859_8()
 * @method static self ISO8859_9()
 * @method static self ISO8859_10()
 * @method static self ISO8859_11()
 * @method static self ISO8859_12()
 * @method static self ISO8859_13()
 * @method static self ISO8859_14()
 * @method static self ISO8859_15()
 * @method static self ISO8859_16()
 * @method static self SJIS()
 * @method static self CP1250()
 * @method static self CP1251()
 * @method static self CP1252()
 * @method static self CP1256()
 * @method static self UNICODE_BIG_UNMARKED()
 * @method static self UTF8()
 * @method static self ASCII()
 * @method static self BIG5()
 * @method static self GB18030()
 * @method static self EUC_KR()
 */
final class Character_Set_Eci extends Abstract_Enum
{
    protected const CP437 = [[0, 2]];
    protected const ISO8859_1 = [[1, 3], 'ISO-8859-1'];
    protected const ISO8859_2 = [[4], 'ISO-8859-2'];
    protected const ISO8859_3 = [[5], 'ISO-8859-3'];
    protected const ISO8859_4 = [[6], 'ISO-8859-4'];
    protected const ISO8859_5 = [[7], 'ISO-8859-5'];
    protected const ISO8859_6 = [[8], 'ISO-8859-6'];
    protected const ISO8859_7 = [[9], 'ISO-8859-7'];
    protected const ISO8859_8 = [[10], 'ISO-8859-8'];
    protected const ISO8859_9 = [[11], 'ISO-8859-9'];
    protected const ISO8859_10 = [[12], 'ISO-8859-10'];
    protected const ISO8859_11 = [[13], 'ISO-8859-11'];
    protected const ISO8859_12 = [[14], 'ISO-8859-12'];
    protected const ISO8859_13 = [[15], 'ISO-8859-13'];
    protected const ISO8859_14 = [[16], 'ISO-8859-14'];
    protected const ISO8859_15 = [[17], 'ISO-8859-15'];
    protected const ISO8859_16 = [[18], 'ISO-8859-16'];
    protected const SJIS = [[20], 'Shift_JIS'];
    protected const CP1250 = [[21], 'windows-1250'];
    protected const CP1251 = [[22], 'windows-1251'];
    protected const CP1252 = [[23], 'windows-1252'];
    protected const CP1256 = [[24], 'windows-1256'];
    protected const UNICODE_BIG_UNMARKED = [[25], 'UTF-16BE', 'UnicodeBig'];
    protected const UTF8 = [[26], 'UTF-8'];
    protected const ASCII = [[27, 170], 'US-ASCII'];
    protected const BIG5 = [[28]];
    protected const GB18030 = [[29], 'GB2312', 'EUC_CN', 'GBK'];
    protected const EUC_KR = [[30], 'EUC-KR'];
    /**
     * @var string[]
     */
    private readonly array $other_encoding_names;
    /**
     * @var array<int, self>|null
     */
    private static ?array $value_to_eci;
    /**
     * @var array<string, self>|null
     */
    private static ?array $name_to_eci = null;
    /**
     * @param int[] $values
     */
    public function __construct(private readonly array $values, string ...$other_encoding_names)
    {
        $this->other_encoding_names = $other_encoding_names;
    }
    /**
     * Returns the primary value.
     */
    public function get_value(): int
    {
        return $this->values[0];
    }
    /**
     * Gets character set ECI by value.
     *
     * Returns the representing ECI of a given value, or null if it is legal but unsupported.
     *
     * @throws InvalidArgumentException if value is not between 0 and 900
     */
    public static function get_character_set_eci_by_value(int $value): ?self
    {
        if ($value < 0 || $value >= 900) {
            throw new InvalidArgumentException('Value must be between 0 and 900');
        }
        $value_to_eci = self::value_to_eci();
        if (!array_key_exists($value, $value_to_eci)) {
            return null;
        }
        return $value_to_eci[$value];
    }
    /**
     * Returns character set ECI by name.
     *
     * Returns the representing ECI of a given name, or null if it is legal but unsupported
     */
    public static function get_character_set_eci_by_name(string $name): ?self
    {
        $name_to_eci = self::name_to_eci();
        $name = strtolower($name);
        if (!array_key_exists($name, $name_to_eci)) {
            return null;
        }
        return $name_to_eci[$name];
    }
    private static function value_to_eci(): array
    {
        if (null !== self::$value_to_eci) {
            return self::$value_to_eci;
        }
        self::$value_to_eci = [];
        foreach (self::values() as $eci) {
            foreach ($eci->values as $value) {
                self::$value_to_eci[$value] = $eci;
            }
        }
        return self::$value_to_eci;
    }
    private static function name_to_eci(): array
    {
        if (null !== self::$name_to_eci) {
            return self::$name_to_eci;
        }
        self::$name_to_eci = [];
        foreach (self::values() as $eci) {
            self::$name_to_eci[strtolower($eci->name())] = $eci;
            foreach ($eci->other_encoding_names as $name) {
                self::$name_to_eci[strtolower($name)] = $eci;
            }
        }
        return self::$name_to_eci;
    }
}