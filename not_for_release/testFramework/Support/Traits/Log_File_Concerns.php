<?php

declare(strict_types=1);

namespace Tests\Support\Traits;

trait LogFileConcerns
{
    public function logFilesExists()
    {
        return glob(DIR_FS_CATALOG . 'logs/myDEBUG*');
    }
}
