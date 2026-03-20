<?php

declare (strict_types=1);
namespace Dasp_Ri_D\Enum;

use Dasp_Ri_D\Enum\Exception\Expectation_Exception;
use Dasp_Ri_D\Enum\Exception\Illegal_Argument_Exception;
use IteratorAggregate;
use Serializable;
use Traversable;
/**
 * A specialized map implementation for use with enum type keys.
 *
 * All of the keys in an enum map must come from a single enum type that is specified, when the map is created. Enum
 * maps are represented internally as arrays. This representation is extremely compact and efficient.
 *
 * Enum maps are maintained in the natural order of their keys (the order in which the enum constants are declared).
 * This is reflected in the iterators returned by the collection views {@see self::getIterator()} and
 * {@see self::values()}.
 *
 * Iterators returned by the collection views are not consistent: They may or may not show the effects of modifications
 * to the map that occur while the iteration is in progress.
 */
final class Enum_Map implements Serializable, IteratorAggregate
{
    /**
     * The class name of the key.
     */
    private readonly string $key_type;
    /**
     * All of the constants comprising the enum, cached for performance.
     *
     * @var array<int, AbstractEnum>
     */
    private $key_universe;
    /**
     * Array representation of this map. The ith element is the value to which universe[i] is currently mapped, or null
     * if it isn't mapped to anything, or NullValue if it's mapped to null.
     *
     * @var array<int, mixed>
     */
    private array $values;
    private int $size = 0;
    /**
     * Creates a new enum map.
     *
     * @param string $keyType the type of the keys, must extend AbstractEnum
     * @param string $valueType the type of the values
     * @param bool $allowNullValues whether to allow null values
     * @throws IllegalArgumentException when key type does not extend AbstractEnum
     */
    public function __construct(
        string $key_type,
        /**
         * The type of the value.
         */
        private readonly string $value_type,
        private readonly bool $allow_null_values
    )
    {
        if (!is_subclass_of($key_type, Abstract_Enum::class)) {
            throw new Illegal_Argument_Exception(sprintf('Class %s does not extend %s', $key_type, Abstract_Enum::class));
        }
        $this->key_type = $key_type;
        $this->key_universe = $key_type::values();
        $this->values = array_fill(0, count($this->key_universe), null);
    }
    public function __serialize(): array
    {
        $values = [];
        foreach ($this->values as $ordinal => $value) {
            if (null === $value) {
                continue;
            }
            $values[$ordinal] = $this->unmask_null($value);
        }
        return ['keyType' => $this->key_type, 'valueType' => $this->value_type, 'allowNullValues' => $this->allow_null_values, 'values' => $values];
    }
    public function __unserialize(array $data): void
    {
        $this->unserialize(serialize($data));
    }
    /**
     * Checks whether the map types match the supplied ones.
     *
     * You should call this method when an EnumMap is passed to you and you want to ensure that it's made up of the
     * correct types.
     *
     * @throws ExpectationException when supplied key type mismatches local key type
     * @throws ExpectationException when supplied value type mismatches local value type
     * @throws ExpectationException when the supplied map allows null values, abut should not
     */
    public function expect(string $key_type, string $value_type, bool $allow_null_values): void
    {
        if ($key_type !== $this->key_type) {
            throw new Expectation_Exception(sprintf('Callee expected an EnumMap with key type %s, but got %s', $key_type, $this->key_type));
        }
        if ($value_type !== $this->value_type) {
            throw new Expectation_Exception(sprintf('Callee expected an EnumMap with value type %s, but got %s', $key_type, $this->key_type));
        }
        if ($allow_null_values !== $this->allow_null_values) {
            throw new Expectation_Exception(sprintf('Callee expected an EnumMap with nullable flag %s, but got %s', $allow_null_values ? 'true' : 'false', $this->allow_null_values ? 'true' : 'false'));
        }
    }
    /**
     * Returns the number of key-value mappings in this map.
     */
    public function size(): int
    {
        return $this->size;
    }
    /**
     * Returns true if this map maps one or more keys to the specified value.
     */
    public function contains_value($value): bool
    {
        return in_array($this->mask_null($value), $this->values, true);
    }
    /**
     * Returns true if this map contains a mapping for the specified key.
     */
    public function contains_key(Abstract_Enum $key): bool
    {
        $this->check_key_type($key);
        return null !== $this->values[$key->ordinal()];
    }
    /**
     * Returns the value to which the specified key is mapped, or null if this map contains no mapping for the key.
     *
     * More formally, if this map contains a mapping from a key to a value, then this method returns the value;
     * otherwise it returns null (there can be at most one such mapping).
     *
     * A return value of null does not necessarily indicate that the map contains no mapping for the key; it's also
     * possible that hte map explicitly maps the key to null. The {@see self::containsKey()} operation may be used to
     * distinguish these two cases.
     *
     * @return mixed
     */
    public function get(Abstract_Enum $key)
    {
        $this->check_key_type($key);
        return $this->unmask_null($this->values[$key->ordinal()]);
    }
    /**
     * Associates the specified value with the specified key in this map.
     *
     * If the map previously contained a mapping for this key, the old value is replaced.
     *
     * @return mixed the previous value associated with the specified key, or null if there was no mapping for the key.
     *               (a null return can also indicate that the map previously associated null with the specified key.)
     * @throws IllegalArgumentException when the passed values does not match the internal value type
     */
    public function put(Abstract_Enum $key, $value)
    {
        $this->check_key_type($key);
        if (!$this->is_valid_value($value)) {
            throw new Illegal_Argument_Exception(sprintf('Value is not of type %s', $this->value_type));
        }
        $index = $key->ordinal();
        $old_value = $this->values[$index];
        $this->values[$index] = $this->mask_null($value);
        if (null === $old_value) {
            ++$this->size;
        }
        return $this->unmask_null($old_value);
    }
    /**
     * Removes the mapping for this key frm this map if present.
     *
     * @return mixed the previous value associated with the specified key, or null if there was no mapping for the key.
     *               (a null return can also indicate that the map previously associated null with the specified key.)
     */
    public function remove(Abstract_Enum $key)
    {
        $this->check_key_type($key);
        $index = $key->ordinal();
        $old_value = $this->values[$index];
        $this->values[$index] = null;
        if (null !== $old_value) {
            --$this->size;
        }
        return $this->unmask_null($old_value);
    }
    /**
     * Removes all mappings from this map.
     */
    public function clear(): void
    {
        $this->values = array_fill(0, count($this->key_universe), null);
        $this->size = 0;
    }
    /**
     * Compares the specified map with this map for quality.
     *
     * Returns true if the two maps represent the same mappings.
     */
    public function equals(self $other): bool
    {
        if ($this === $other) {
            return true;
        }
        if ($this->size !== $other->size) {
            return false;
        }
        return $this->values === $other->values;
    }
    /**
     * Returns the values contained in this map.
     *
     * The array will contain the values in the order their corresponding keys appear in the map, which is their natural
     * order (the order in which the num constants are declared).
     */
    public function values(): array
    {
        return array_values(array_map($this->unmask_null(...), array_filter($this->values, fn($value): bool => null !== $value)));
    }
    public function serialize(): string
    {
        return serialize($this->__serialize());
    }
    public function unserialize($serialized): void
    {
        $data = unserialize($serialized);
        $this->__construct($data['keyType'], $data['valueType'], $data['allowNullValues']);
        foreach ($this->key_universe as $key) {
            if (array_key_exists($key->ordinal(), $data['values'])) {
                $this->put($key, $data['values'][$key->ordinal()]);
            }
        }
    }
    public function getIterator(): Traversable
    {
        foreach ($this->key_universe as $key) {
            if (null === $this->values[$key->ordinal()]) {
                continue;
            }
            yield $key => $this->unmask_null($this->values[$key->ordinal()]);
        }
    }
    private function mask_null($value)
    {
        if (null === $value) {
            return Null_Value::instance();
        }
        return $value;
    }
    private function unmask_null($value)
    {
        if ($value instanceof Null_Value) {
            return null;
        }
        return $value;
    }
    /**
     * @throws IllegalArgumentException when the passed key does not match the internal key type
     */
    private function check_key_type(Abstract_Enum $key): void
    {
        if ($key::class !== $this->key_type) {
            throw new Illegal_Argument_Exception(sprintf('Object of type %s is not the same type as %s', $key::class, $this->key_type));
        }
    }
    private function is_valid_value($value): bool
    {
        if (null === $value) {
            if ($this->allow_null_values) {
                return true;
            }
            return false;
        }
        return match ($this->value_type) {
            'mixed' => true,
            'bool', 'boolean' => is_bool($value),
            'int', 'integer' => is_int($value),
            'float', 'double' => is_float($value),
            'string' => is_string($value),
            'object' => is_object($value),
            'array' => is_array($value),
            default => $value instanceof $this->value_type,
        };
    }
}