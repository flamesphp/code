<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ChangesReporting\Output;

use Flames\Code\Upgrade\ChangesReporting\Contract\Output\OutputFormatterInterface;
use Flames\Code\Upgrade\ChangesReporting\Output\Factory\JsonOutputFactory;
use Flames\Code\Upgrade\Reporting\UnusedSkipResolver;
use Flames\Code\Upgrade\ValueObject\Configuration;
use Flames\Code\Upgrade\ValueObject\ProcessResult;
final readonly class JsonOutputFormatter implements OutputFormatterInterface
{
    public const string NAME = 'json';
    public function __construct(private UnusedSkipResolver $unusedSkipResolver)
    {
    }
    public function getName(): string
    {
        return self::NAME;
    }
    public function report(ProcessResult $processResult, Configuration $configuration): void
    {
        // console output is silenced in json mode, so unused skips are surfaced in the payload
        $unusedSkips = $this->unusedSkipResolver->resolve($processResult);
        echo JsonOutputFactory::create($processResult, $configuration, $unusedSkips) . \PHP_EOL;
    }
}
