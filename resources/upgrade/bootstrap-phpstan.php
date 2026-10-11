<?php

declare(strict_types=1);

if (interface_exists(\PHPStan\Type\Type::class, false)) {
    return;
}

require_once __DIR__ . '/php-parser-bridge.php';
require_once __DIR__ . '/phpdoc-parser-bridge.php';
require_once __DIR__ . '/nette-bridge.php';
require_once __DIR__ . '/hoa-bridge.php';
require_once __DIR__ . '/jetbrains-stubs-bridge.php';

$phpStanBootstrap = __DIR__ . '/../../Flames/Code/Upgrade/ThirdParty/PHPStan/bootstrap.php';

if (! is_file($phpStanBootstrap)) {
    throw new \RuntimeException(
        'Bundled PHPStan engine was not found at Flames/Code/Upgrade/ThirdParty/PHPStan/. '
        . 'Run: php scripts/bundle-phpstan-thirdparty.php && php scripts/unify-phpstan-thirdparty.php',
    );
}

require_once $phpStanBootstrap;
