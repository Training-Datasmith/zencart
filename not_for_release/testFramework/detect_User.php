<?php

declare(strict_types=1);
$user = $_SERVER['USER'] ?? $_SERVER['MY_USER'];
echo 'Detected User - ' . $user  ."\n";
