<?php

declare(strict_types=1);
$dir = __DIR__;

spl_autoload_register(function ($class) use ($dir): bool {
    $classMap = [
        // "mobiledetect/mobiledetectlib"
        \Detection\Cache\Cache::class => $dir . '/../src/Cache/Cache.php',
        \Detection\Cache\CacheException::class => $dir . '/../src/Cache/CacheException.php',
        \Detection\Cache\CacheInvalidArgumentException::class => $dir . '/../src/Cache/CacheInvalidArgumentException.php',
        \Detection\Exception\MobileDetectException::class => $dir . '/../src/Exception/MobileDetectException.php',
        \Detection\Exception\MobileDetectExceptionCode::class => $dir . '/../src/Exception/MobileDetectExceptionCode.php',
        \Detection\MobileDetect::class => $dir . '/../src/MobileDetect.php',

        // "psr/simple-cache"
        \Psr\SimpleCache\CacheException::class => $dir . '/deps/simple-cache/src/CacheException.php',
        \Psr\SimpleCache\CacheInterface::class => $dir . '/deps/simple-cache/src/CacheInterface.php',
        \Psr\SimpleCache\InvalidArgumentException::class => $dir . '/deps/simple-cache/src/InvalidArgumentException.php',
    ];

    $fileFound = $classMap[$class] ?? false;

    if ($fileFound) {
        require $fileFound;
        return true;
    }

    return false;
});
