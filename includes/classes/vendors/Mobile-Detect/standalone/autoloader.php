<?php

declare (strict_types=1);
$dir = __DIR__;
spl_autoload_register(function ($class) use ($dir): bool {
    $class_map = [
        // "mobiledetect/mobiledetectlib"
        \Detection\Cache\Cache::class => $dir . '/../src/Cache/Cache.php',
        \Detection\Cache\Cache_Exception::class => $dir . '/../src/Cache/CacheException.php',
        \Detection\Cache\Cache_Invalid_Argument_Exception::class => $dir . '/../src/Cache/CacheInvalidArgumentException.php',
        \Detection\Exception\Mobile_Detect_Exception::class => $dir . '/../src/Exception/MobileDetectException.php',
        \Detection\Exception\Mobile_Detect_Exception_Code::class => $dir . '/../src/Exception/MobileDetectExceptionCode.php',
        \Detection\Mobile_Detect::class => $dir . '/../src/MobileDetect.php',
        // "psr/simple-cache"
        \Psr\Simple_Cache\Cache_Exception::class => $dir . '/deps/simple-cache/src/CacheException.php',
        \Psr\Simple_Cache\Cache_Interface::class => $dir . '/deps/simple-cache/src/CacheInterface.php',
        \Psr\Simple_Cache\InvalidArgumentException::class => $dir . '/deps/simple-cache/src/InvalidArgumentException.php',
    ];
    $file_found = $class_map[$class] ?? false;
    if ($file_found) {
        require $file_found;
        return true;
    }
    return false;
});