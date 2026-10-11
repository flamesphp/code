<?php

declare(strict_types=1);

namespace Flames\Code\Upgrade;

/**
 * Absolute paths inside the flamesphp/code package.
 *
 * @internal
 */
final class PackagePath
{
    public static function root(): string
    {
        return dirname(__DIR__, 3) . '/';
    }

    public static function resource(string $path = ''): string
    {
        return self::root() . 'resources/upgrade/' . ltrim($path, '/');
    }
}
