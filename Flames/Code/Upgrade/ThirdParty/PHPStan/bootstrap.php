<?php declare(strict_types = 1);

namespace PHPStan;

use function class_exists;
use const PHP_VERSION_ID;

final class PharAutoloader
{
    private static bool $polyfillsLoaded = false;

    final public static function loadClass(string $class): void
    {
        if (defined('__PHPSTAN_RUNNING__')) {
            return;
        }

        if (str_starts_with($class, 'Flames\\Code\\Upgrade\\ThirdParty\\')) {
            return;
        }

        if (str_starts_with($class, 'PHPStan\\PhpDocParser\\')) {
            return;
        }

        if (! str_starts_with($class, 'PHPStan\\')) {
            return;
        }

        if (! self::$polyfillsLoaded) {
            self::$polyfillsLoaded = true;
            self::loadPolyfills();
        }

        $relative = substr($class, strlen('PHPStan\\'));

        if (str_starts_with($class, 'PHPStan\\BetterReflection\\')) {
            $filepath = __DIR__ . '/../BetterReflection/src/' . str_replace('\\', '/', substr($relative, strlen('BetterReflection\\'))) . '.php';
        } elseif ($class === 'PHPStan\\Php8StubsMap') {
            $filepath = __DIR__ . '/Php8Stubs/Php8StubsMap.php';
        } else {
            $filepath = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
        }

        if (is_file($filepath)) {
            require $filepath;
        }
    }

    public static function warmup(): void
    {
        if (! self::$polyfillsLoaded) {
            self::$polyfillsLoaded = true;
            self::loadPolyfills();
        }
    }

    private static function loadPolyfills(): void
    {
        $thirdParty = dirname(__DIR__);

        $includes = [
            __DIR__ . '/autoloadFunctions.php',
            __DIR__ . '/debugScope.php',
            __DIR__ . '/dumpType.php',
            $thirdParty . '/Symfony/Component/String/functions.php',
            $thirdParty . '/Symfony/Contracts/Deprecation/function.php',
            $thirdParty . '/Hoa/Consistency/Prelude.php',
            $thirdParty . '/Hoa/Protocol/Wrapper.php',
            $thirdParty . '/React/Promise/functions_include.php',
            $thirdParty . '/Ralouphie/getallheaders.php',
            $thirdParty . '/GuzzleHttp/functions_include.php',
            $thirdParty . '/JetBrains/phpstorm-stubs/PhpStormStubsMap.php',
        ];

        foreach ($includes as $file) {
            if (is_file($file)) {
                require_once $file;
            }
        }

        $polyfills = [
            [80000, 'a4a119a56e50fbb293281d9a48007e0e', 'Symfony/Polyfill/Php80/Php80.php', 'Symfony/Polyfill/Php80/bootstrap.php'],
            [PHP_INT_MAX, '0e6d7bf4a5811bfa5cf40c5ccd6fae6a', 'Symfony/Polyfill/Mbstring/Mbstring.php', 'Symfony/Polyfill/Mbstring/bootstrap.php'],
            [PHP_INT_MAX, 'e69f7f6ee287b969198c3c9d6777bd38', 'Symfony/Polyfill/Intl/Normalizer/Normalizer.php', 'Symfony/Polyfill/Intl/Normalizer/bootstrap.php'],
            [PHP_INT_MAX, '8825ede83f2f289127722d4e842cf7e8', 'Symfony/Polyfill/Intl/Grapheme/Grapheme.php', 'Symfony/Polyfill/Intl/Grapheme/bootstrap.php'],
            [80100, '23c18046f52bef3eea034657bafda50f', 'Symfony/Polyfill/Php81/Php81.php', 'Symfony/Polyfill/Php81/bootstrap.php'],
            [80300, '662a729f963d39afe703c9d9b7ab4a8c', 'Symfony/Polyfill/Php83/Php83.php', 'Symfony/Polyfill/Php83/bootstrap.php'],
            [80400, '9d2b9fc6db0f153a0a149fefb182415e', 'Symfony/Polyfill/Php84/Php84.php', 'Symfony/Polyfill/Php84/bootstrap.php'],
            [80500, '606a39d89246991a373564698c2d8383', 'Symfony/Polyfill/Php85/Php85.php', 'Symfony/Polyfill/Php85/bootstrap.php'],
        ];

        foreach ($polyfills as [$maxPhp, $hash, $main, $bootstrap]) {
            if (PHP_VERSION_ID >= $maxPhp) {
                continue;
            }

            if (! empty($GLOBALS['__composer_autoload_files'][$hash])) {
                continue;
            }

            $mainFile = $thirdParty . '/' . $main;
            $bootstrapFile = $thirdParty . '/' . $bootstrap;

            if (! is_file($mainFile)) {
                continue;
            }

            $GLOBALS['__composer_autoload_files'][$hash] = true;
            require_once $mainFile;

            if (is_file($bootstrapFile)) {
                require_once $bootstrapFile;
            }
        }
    }
}

spl_autoload_register([PharAutoloader::class, 'loadClass']);

require_once __DIR__ . '/autoloadFunctions.php';

PharAutoloader::warmup();
