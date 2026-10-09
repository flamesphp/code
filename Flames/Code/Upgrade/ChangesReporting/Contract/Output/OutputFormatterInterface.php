<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ChangesReporting\Contract\Output;

use Flames\Code\Upgrade\ValueObject\Configuration;
use Flames\Code\Upgrade\ValueObject\ProcessResult;
interface OutputFormatterInterface
{
    public function getName(): string;
    public function report(ProcessResult $processResult, Configuration $configuration): void;
}
