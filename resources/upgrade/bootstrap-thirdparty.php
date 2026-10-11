<?php

declare(strict_types=1);

if (defined('__FLAMES_THIRDPARTY_AUTOLOAD_CONFIGURED__')) {
    return null;
}

define('__FLAMES_THIRDPARTY_AUTOLOAD_CONFIGURED__', true);

$upgradeDir = __DIR__;

// Legacy scoper autoload (composer classmap + function aliases for polyfills).
$scoperAutoload = $upgradeDir . '/thirdparty/scoper-autoload.php';
if (is_file($scoperAutoload)) {
    require_once $scoperAutoload;
}

return null;
