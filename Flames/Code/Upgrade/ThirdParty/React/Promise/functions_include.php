<?php

declare(strict_types=1);

namespace Flames\Code\Upgrade;

if (! \function_exists('Flames\Code\Upgrade\ThirdParty\React\Promise\resolve')) {
    require __DIR__ . '/functions.php';
}
