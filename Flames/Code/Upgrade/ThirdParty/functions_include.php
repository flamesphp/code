<?php

namespace Flames\Code\Upgrade;

if (!\function_exists('Flames\Code\Upgrade\ThirdParty\React\Promise\resolve')) {
    require __DIR__ . '/functions.php';
}
