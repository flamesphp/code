<?php

declare(strict_types=1);

/**
 * Single PhpParser library: Flames/Code/Upgrade/ThirdParty/PhpParser.
 *
 * PHPStan and BetterReflection use the PhpParser\ namespace. This bridge loads
 * the scoped ThirdParty copy and registers class_alias() so both namespaces
 * share one implementation.
 *
 * @internal
 */
function flamesPhpParserBridgeRegister(): void
{
    static $registered = false;

    if ($registered) {
        return;
    }

    $registered = true;

    $thirdPartyPhpParserRoot = dirname(__DIR__, 2) . '/Flames/Code/Upgrade/ThirdParty/PhpParser';
    $flamesPrefix = 'Flames\\Code\\Upgrade\\ThirdParty\\PhpParser\\';

    $loadThirdPartyPhpParserFile = static function (string $relativePath) use ($thirdPartyPhpParserRoot): bool {
        $file = $thirdPartyPhpParserRoot . '/' . $relativePath;

        if (! is_file($file)) {
            return false;
        }

        require_once $file;

        return true;
    };

    $aliasPhpParserClass = static function (string $flamesClass) use ($flamesPrefix): void {
        if (! str_starts_with($flamesClass, $flamesPrefix)) {
            return;
        }

        $phpParserClass = 'PhpParser\\' . substr($flamesClass, strlen($flamesPrefix));

        if (
            class_exists($phpParserClass, false)
            || interface_exists($phpParserClass, false)
            || trait_exists($phpParserClass, false)
        ) {
            return;
        }

        if (
            ! class_exists($flamesClass, false)
            && ! interface_exists($flamesClass, false)
            && ! trait_exists($flamesClass, false)
        ) {
            return;
        }

        class_alias($flamesClass, $phpParserClass);
    };

    spl_autoload_register(static function (string $class) use ($flamesPrefix, $loadThirdPartyPhpParserFile, $aliasPhpParserClass): void {
        if (! str_starts_with($class, $flamesPrefix)) {
            return;
        }

        if (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false)) {
            return;
        }

        $relativePath = str_replace('\\', '/', substr($class, strlen($flamesPrefix))) . '.php';

        if (! $loadThirdPartyPhpParserFile($relativePath)) {
            return;
        }

        $aliasPhpParserClass($class);
    }, prepend: true);

    spl_autoload_register(static function (string $class) use ($flamesPrefix, $loadThirdPartyPhpParserFile, $aliasPhpParserClass): void {
        if (! str_starts_with($class, 'PhpParser\\')) {
            return;
        }

        if (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false)) {
            return;
        }

        $flamesClass = $flamesPrefix . substr($class, strlen('PhpParser\\'));

        if (class_exists($flamesClass, false) || interface_exists($flamesClass, false) || trait_exists($flamesClass, false)) {
            class_alias($flamesClass, $class);

            return;
        }

        $relativePath = str_replace('\\', '/', substr($class, strlen('PhpParser\\'))) . '.php';

        if (! $loadThirdPartyPhpParserFile($relativePath)) {
            return;
        }

        $aliasPhpParserClass($flamesClass);

        if (! class_exists($class, false) && ! interface_exists($class, false) && ! trait_exists($class, false)) {
            class_alias($flamesClass, $class);
        }
    }, prepend: true);

    spl_autoload_register(static function (string $class) use ($flamesPrefix, $aliasPhpParserClass): void {
        if (! str_starts_with($class, $flamesPrefix)) {
            return;
        }

        if (! class_exists($class, false) && ! interface_exists($class, false) && ! trait_exists($class, false)) {
            return;
        }

        $aliasPhpParserClass($class);
    }, prepend: false);
}

flamesPhpParserBridgeRegister();

$phpParserClassAliases = __DIR__ . '/phpparser-class-aliases.php';

if (is_file($phpParserClassAliases)) {
    require_once $phpParserClassAliases;
}
