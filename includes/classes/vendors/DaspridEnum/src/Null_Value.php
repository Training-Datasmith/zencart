<?php

declare (strict_types=1);
namespace Dasp_Ri_D\Enum;

use Dasp_Ri_D\Enum\Exception\Clone_Not_Supported_Exception;
use Dasp_Ri_D\Enum\Exception\Serialize_Not_Supported_Exception;
use Dasp_Ri_D\Enum\Exception\Unserialize_Not_Supported_Exception;
final class Null_Value
{
    private static ?\Dasp_Ri_D\Enum\Null_Value $instance = null;
    private function __construct()
    {
    }
    public static function instance(): self
    {
        return self::$instance ?: self::$instance = new self();
    }
    /**
     * Forbid cloning enums.
     *
     * @throws CloneNotSupportedException
     */
    final public function __clone()
    {
        throw new Clone_Not_Supported_Exception();
    }
    /**
     * Forbid serializing enums.
     *
     * @throws SerializeNotSupportedException
     */
    final public function __sleep(): array
    {
        throw new Serialize_Not_Supported_Exception();
    }
    /**
     * Forbid serializing enums.
     *
     * @throws SerializeNotSupportedException
     */
    final public function __serialize(): array
    {
        throw new Serialize_Not_Supported_Exception();
    }
    /**
     * Forbid unserializing enums.
     *
     * @throws UnserializeNotSupportedException
     */
    final public function __wakeup(): void
    {
        throw new Unserialize_Not_Supported_Exception();
    }
    /**
     * Forbid unserializing enums.
     *
     * @throws UnserializeNotSupportedException
     */
    final public function __unserialize($arg): void
    {
        throw new Unserialize_Not_Supported_Exception();
    }
}