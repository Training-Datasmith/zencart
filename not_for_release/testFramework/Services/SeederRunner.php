<?php

declare(strict_types=1);

namespace Tests\Services;

/**
 * @since ZC v2.0.0
 */
class SeederRunner
{
    /**
     * @since ZC v2.0.0
     */
    public function run(string $seederClass, $parameters = []): void
    {
        $namespace = '\\Seeders\\';
        $class = $namespace . $seederClass;
        $seeder = new $class();
        $seeder->run($parameters);
    }
}
