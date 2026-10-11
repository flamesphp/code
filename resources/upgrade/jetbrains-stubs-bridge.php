<?php

declare(strict_types=1);

/**
 * PhpStormStubsMap: Flames\Code\Upgrade\ThirdParty\JetBrains\PHPStormStub.
 *
 * @internal
 */
function flamesJetBrainsStubsBridgeRegister(): void
{
    static $registered = false;

    if ($registered) {
        return;
    }

    $registered = true;

    $flamesClass = 'Flames\\Code\\Upgrade\\ThirdParty\\JetBrains\\PHPStormStub\\PhpStormStubsMap';
    $mapFile = dirname(__DIR__, 2)
        . '/Flames/Code/Upgrade/ThirdParty/JetBrains/phpstorm-stubs/PhpStormStubsMap.php';

    if (! class_exists($flamesClass, false) && is_file($mapFile)) {
        require_once $mapFile;
    }
}

flamesJetBrainsStubsBridgeRegister();
