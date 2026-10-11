<?php

declare(strict_types=1);

/**
 * Single phpdoc-parser: Flames/Code/Upgrade/ThirdParty/PHPStan.
 *
 * PHPStan uses PHPStan\PhpDocParser\ namespace; ThirdParty copy lives under
 * Flames\Code\Upgrade\ThirdParty\PHPStan\ (PhpDocParser segment removed).
 *
 * @internal
 */
function flamesPhpDocParserBridgeRegister(): void
{
    static $registered = false;

    if ($registered) {
        return;
    }

    $registered = true;

    $thirdPartyRoot = dirname(__DIR__, 2) . '/Flames/Code/Upgrade/ThirdParty/PHPStan';
    $flamesPrefix = 'Flames\\Code\\Upgrade\\ThirdParty\\PHPStan\\';

    $loadFile = static function (string $relativePath) use ($thirdPartyRoot): bool {
        $file = $thirdPartyRoot . '/' . $relativePath;

        if (! is_file($file)) {
            return false;
        }

        require_once $file;

        return true;
    };

    spl_autoload_register(static function (string $class) use ($flamesPrefix, $loadFile): void {
        if (! str_starts_with($class, $flamesPrefix)) {
            return;
        }

        if (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false)) {
            return;
        }

        $relativePath = str_replace('\\', '/', substr($class, strlen($flamesPrefix))) . '.php';
        $loadFile($relativePath);
    }, prepend: true);

    spl_autoload_register(static function (string $class) use ($flamesPrefix, $loadFile): void {
        if (! str_starts_with($class, 'PHPStan\\PhpDocParser\\')) {
            return;
        }

        if (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false)) {
            return;
        }

        $flamesClass = $flamesPrefix . substr($class, strlen('PHPStan\\PhpDocParser\\'));

        if (class_exists($flamesClass, false) || interface_exists($flamesClass, false) || trait_exists($flamesClass, false)) {
            class_alias($flamesClass, $class);

            return;
        }

        $relativePath = str_replace('\\', '/', substr($class, strlen('PHPStan\\PhpDocParser\\'))) . '.php';

        if (! $loadFile($relativePath)) {
            return;
        }

        if (! class_exists($flamesClass, false) && ! interface_exists($flamesClass, false) && ! trait_exists($flamesClass, false)) {
            return;
        }

        if (! class_exists($class, false) && ! interface_exists($class, false) && ! trait_exists($class, false)) {
            class_alias($flamesClass, $class);
        }
    }, prepend: true);
}

flamesPhpDocParserBridgeRegister();

$phpDocParserClassAliases = __DIR__ . '/phpdoc-parser-class-aliases.php';

if (is_file($phpDocParserClassAliases)) {
    require_once $phpDocParserClassAliases;
}
