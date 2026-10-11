<?php

declare(strict_types=1);

/**
 * Bridge PHPStan phar autoload infra to vendor/flamesphp/composer.
 *
 * @internal
 */
function flamesUpgradeComposerBridge(): void
{
    static $registered = false;
    if ($registered) {
        return;
    }
    $registered = true;

    $composerPackageRoot = dirname(__DIR__, 3) . '/composer/Flames/Composer';

    $classLoader = $composerPackageRoot . '/Composer/Autoload/loader.php';
    if (is_file($classLoader)) {
        require_once $classLoader;
    }

    $installedVersions = $composerPackageRoot . '/Composer/is.php';
    if (is_file($installedVersions)) {
        require_once $installedVersions;
    }

    if (! class_exists(\Composer\Autoload\ClassLoader::class, false)
        && class_exists(\Flames\Composer\Composer\Autoload\ClassLoader::class, false)) {
        class_alias(\Flames\Composer\Composer\Autoload\ClassLoader::class, \Composer\Autoload\ClassLoader::class);
    }

    if (! class_exists(\Composer\InstalledVersions::class, false)
        && class_exists(\Flames\Composer\Composer\InstalledVersions::class, false)) {
        class_alias(\Flames\Composer\Composer\InstalledVersions::class, \Composer\InstalledVersions::class);
    }
}

flamesUpgradeComposerBridge();
