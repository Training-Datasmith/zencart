<?php

declare(strict_types=1);

namespace Seeders;

use Tests\Support\Database\TestDb;

class StoreWizardSeeder
{
    /**
     * Auto generated seed file
     */
    public function run(): void
    {
        TestDb::update(
            'configuration',
            ['configuration_value' => 'Zencart Store Name'],
            'configuration_key = :config_key',
            [':config_key' => 'STORE_NAME']
        );
        TestDb::update(
            'configuration',
            ['configuration_value' => 'Zencart Store Owner'],
            'configuration_key = :config_key',
            [':config_key' => 'STORE_OWNER']
        );
    }
}
