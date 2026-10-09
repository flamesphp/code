<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Console;

use FlamesPrefix202610\Symfony\Component\Console\Command\Command;
/**
 * @api
 */
final class ExitCode
{
    public const int SUCCESS = Command::SUCCESS;
    public const int FAILURE = Command::FAILURE;
    public const int CHANGED_CODE = 2;
}
