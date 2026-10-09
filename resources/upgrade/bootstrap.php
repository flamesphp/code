<?php

declare(strict_types=1);

use PHPParser\Node;
use PHPUnit\Runner\Version;

/**
 * The preload.php preloads scoped third-party parsers early:
 *      - phpstan/phpdoc-parser (Flames/Code/Upgrade/ThirdParty/PHPStan/)
 *      - nikic/php-parser (Flames/Code/Upgrade/ThirdParty/PhpParser/)
 *
 * Full PHPStan (analyser) loads from resources/upgrade/thirdparty/phpstan/ via bootstrap-phpstan.php.
 */
if (
    defined('PHPUNIT_COMPOSER_INSTALL')
    && ! interface_exists(Node::class, false)
    && ! class_exists(Version::class, false)
    && class_exists(Version::class, true) && (int) Version::id() >= 12
) {
    require_once __DIR__ . '/preload.php';
}

// Flames\Code\Upgrade\* (including migrated thirdparty) loads via flamesphp/autoload.
