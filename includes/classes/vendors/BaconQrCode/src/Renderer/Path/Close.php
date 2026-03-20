<?php

declare (strict_types=1);
namespace Bacon_Qr_Code\Renderer\Path;

final class Close implements Operation_Interface
{
    private static ?Close $instance = null;
    private function __construct()
    {
    }
    public static function instance(): self
    {
        return self::$instance ?: self::$instance = new self();
    }
    /**
     * @return self
     */
    public function translate(float $x, float $y): Operation_Interface
    {
        return $this;
    }
    /**
     * @return self
     */
    public function rotate(int $degrees): Operation_Interface
    {
        return $this;
    }
}