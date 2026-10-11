<?php

declare (strict_types=1);
namespace PHPStan\Command\Symfony;

use PHPStan\Command\Output;
use PHPStan\Command\OutputStyle;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Output\OutputInterface;
/**
 * @internal
 */
final class SymfonyOutput implements Output
{
    private OutputInterface $symfonyOutput;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public OutputStyle $style;
    public function __construct(
        OutputInterface $symfonyOutput,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        OutputStyle $style
    )
    {
        $this->symfonyOutput = $symfonyOutput;
        $this->style = $style;
    }
    public function writeFormatted(string $message): void
    {
        $this->symfonyOutput->write($message, \false, OutputInterface::OUTPUT_NORMAL);
    }
    public function writeLineFormatted(string $message): void
    {
        $this->symfonyOutput->writeln($message, OutputInterface::OUTPUT_NORMAL);
    }
    public function writeRaw(string $message): void
    {
        $this->symfonyOutput->write($message, \false, OutputInterface::OUTPUT_RAW);
    }
    public function getStyle(): OutputStyle
    {
        return $this->style;
    }
    public function isVerbose(): bool
    {
        return $this->symfonyOutput->isVerbose();
    }
    public function isVeryVerbose(): bool
    {
        return $this->symfonyOutput->isVeryVerbose();
    }
    public function isDebug(): bool
    {
        return $this->symfonyOutput->isDebug();
    }
    public function isDecorated(): bool
    {
        return $this->symfonyOutput->isDecorated();
    }
}
