<?php

declare(strict_types=1);

use PHPParser\Node;
use PHPUnit\Runner\Version;

/**
 * The preload.php contains 2 dependencies
 *      - phpstan/phpdoc-parser
 *      - nikic/php-parser
 *
 * They need to be loaded early to avoid conflict version between prefixed thirdparty and project vendor.
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
