<?php

declare(strict_types=1);

if (class_exists(\PHPStan\Type\Type::class, false)) {
    return;
}

$roots = [];

if (defined('ROOT_PATH')) {
    $roots[] = rtrim(ROOT_PATH, '/') . '/';
}

$roots[] = dirname(__DIR__, 5) . '/';

foreach ($roots as $root) {
    foreach ([
        'vendor/phpstan/phpstan/bootstrap.php',
        'vendor/phpstan2/phpstan/bootstrap.php',
    ] as $relative) {
        $bootstrap = $root . $relative;
        if (is_file($bootstrap)) {
            require_once $bootstrap;
            return;
        }
    }
}
