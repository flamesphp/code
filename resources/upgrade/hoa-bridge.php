<?php

declare(strict_types=1);

/**
 * Map legacy Hoa\ namespace to scoped ThirdParty copy.
 *
 * @internal
 */
function flamesHoaBridgeRegister(): void
{
    static $registered = false;

    if ($registered) {
        return;
    }

    $registered = true;

    $hoaRoot = dirname(__DIR__, 2) . '/Flames/Code/Upgrade/ThirdParty/Hoa';
    $flamesPrefix = 'Flames\\Code\\Upgrade\\ThirdParty\\Hoa\\';

    spl_autoload_register(static function (string $class) use ($hoaRoot, $flamesPrefix): void {
        if (! str_starts_with($class, 'Hoa\\')) {
            return;
        }

        if (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false)) {
            return;
        }

        $flamesClass = $flamesPrefix . substr($class, strlen('Hoa\\'));

        if (class_exists($flamesClass, false) || interface_exists($flamesClass, false) || trait_exists($flamesClass, false)) {
            class_alias($flamesClass, $class);

            return;
        }

        $relativePath = str_replace('\\', '/', substr($class, strlen('Hoa\\'))) . '.php';
        $path = $hoaRoot . '/' . $relativePath;

        if (! is_file($path)) {
            return;
        }

        require_once $path;

        if (class_exists($flamesClass, false) || interface_exists($flamesClass, false) || trait_exists($flamesClass, false)) {
            if (! class_exists($class, false) && ! interface_exists($class, false) && ! trait_exists($class, false)) {
                class_alias($flamesClass, $class);
            }
        }
    }, prepend: true);
}

flamesHoaBridgeRegister();
