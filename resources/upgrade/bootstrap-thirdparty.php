<?php

declare(strict_types=1);

if (defined('__FLAMES_THIRDPARTY_AUTOLOAD_CONFIGURED__')) {
    return null;
}

define('__FLAMES_THIRDPARTY_AUTOLOAD_CONFIGURED__', true);

$loader = require __DIR__ . '/thirdparty/scoper-autoload.php';

if ($loader instanceof \Composer\Autoload\ClassLoader) {
    // PSR-4 paths (rector-phpunit, rector-symfony, …) are not fully classmapped.
    $loader->setClassMapAuthoritative(false);
}

return $loader;
