<?php

declare(strict_types=1);

/**
 * Nette ThirdParty autoload helpers (multi-class files + early exception base types).
 *
 * @internal
 */
function flamesNetteBridgeRegister(): void
{
    static $registered = false;

    if ($registered) {
        return;
    }

    $registered = true;

    $netteRoot = dirname(__DIR__, 2) . '/Flames/Code/Upgrade/ThirdParty/Nette';

    require_once $netteRoot . '/exceptions.php';

    $multiClassFiles = [
        'Flames\\Code\\Upgrade\\ThirdParty\\Nette\\DI\\MissingServiceException' => $netteRoot . '/DI/MissingServiceException.php',
        'Flames\\Code\\Upgrade\\ThirdParty\\Nette\\DI\\NotAllowedDuringResolvingException' => $netteRoot . '/DI/MissingServiceException.php',
        'Flames\\Code\\Upgrade\\ThirdParty\\Nette\\DI\\InvalidConfigurationException' => $netteRoot . '/DI/MissingServiceException.php',
    ];

    spl_autoload_register(static function (string $class) use ($netteRoot, $multiClassFiles): void {
        if (! str_starts_with($class, 'Flames\\Code\\Upgrade\\ThirdParty\\Nette\\')) {
            return;
        }

        if (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false)) {
            return;
        }

        if (isset($multiClassFiles[$class]) && is_file($multiClassFiles[$class])) {
            require_once $multiClassFiles[$class];

            return;
        }

        $relative = substr(str_replace('\\', '/', $class), strlen('Flames/Code/Upgrade/ThirdParty/Nette/'));
        $path = $netteRoot . '/' . $relative . '.php';

        if (is_file($path)) {
            require_once $path;
        }
    }, prepend: true);
}

flamesNetteBridgeRegister();
