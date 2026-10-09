<?php

declare(strict_types=1);

if (class_exists(\PHPStan\Type\Type::class, false)) {
    return;
}

$bundledBootstrap = __DIR__ . '/thirdparty/phpstan/bootstrap.php';

if (! is_file($bundledBootstrap)) {
    throw new \RuntimeException(
        'Bundled PHPStan was not found at resources/upgrade/thirdparty/phpstan/. '
        . 'Run: php scripts/bundle-phpstan-thirdparty.php',
    );
}

require_once $bundledBootstrap;
