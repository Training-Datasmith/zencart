<?php

declare(strict_types=1);

namespace Seeders;

use Tests\Support\Database\TestDb;

class DisplayLogsSeeder
{
    /**
     * Auto generated seed file
     */
    public function run(): void
    {
        TestDb::insert('configuration', ['configuration_key' => 'DISPLAY_LOGS_MAX_DISPLAY', 'configuration_value' => '20', 'configuration_group_id' => 10, 'configuration_title' => 'foo', 'configuration_description' => 'foo']);
        TestDb::insert('configuration', ['configuration_key' => 'DISPLAY_LOGS_MAX_FILE_SIZE', 'configuration_value' => '80000', 'configuration_group_id' => 10, 'configuration_title' => 'foo', 'configuration_description' => 'foo']);
        TestDb::insert('configuration', ['configuration_key' => 'DISPLAY_LOGS_INCLUDED_FILES', 'configuration_value' => '', 'configuration_group_id' => 10, 'configuration_title' => 'foo', 'configuration_description' => 'foo']);
        TestDb::insert('configuration', ['configuration_key' => 'DISPLAY_LOGS_EXCLUDED_FILES', 'configuration_value' => '', 'configuration_group_id' => 10, 'configuration_title' => 'foo', 'configuration_description' => 'foo']);
    }
}
