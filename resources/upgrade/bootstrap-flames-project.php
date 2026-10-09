<?php

declare(strict_types=1);

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 5) . '/');
}

if (! defined('APP_PATH')) {
    define('APP_PATH', ROOT_PATH . 'App/');
}

$flamesAutoload = ROOT_PATH . 'vendor/flamesphp/autoload/Flames/Autoload/Autoload.php';

if (is_file($flamesAutoload) && ! class_exists(\Flames\Autoload\Autoload::class, false)) {
    require_once $flamesAutoload;
    \Flames\Autoload\Autoload::run();
}

$resetData = ROOT_PATH . 'vendor/flamesphp/ready/Flames/Ready/ResetData.php';

if (is_file($resetData)) {
    require_once $resetData;
}
