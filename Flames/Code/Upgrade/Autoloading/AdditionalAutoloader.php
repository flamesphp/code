<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Autoloading;

use Flames\Code\Upgrade\Configuration\Option;
use Flames\Code\Upgrade\Configuration\Parameter\SimpleParameterProvider;
use Flames\Code\Upgrade\StaticReflection\DynamicSourceLocatorDecorator;
use FlamesPrefix202610\Symfony\Component\Console\Input\InputInterface;
use FlamesPrefix202610\Webmozart\Assert\Assert;
/**
 * Should it pass autoload files/directories to PHPStan analyzer?
 */
final readonly class AdditionalAutoloader
{
    public function __construct(private DynamicSourceLocatorDecorator $dynamicSourceLocatorDecorator)
    {
    }
    public function autoloadInput(InputInterface $input): void
    {
        if (!$input->hasOption(Option::AUTOLOAD_FILE)) {
            return;
        }
        /** @var string|null $autoloadFile */
        $autoloadFile = $input->getOption(Option::AUTOLOAD_FILE);
        if ($autoloadFile === null) {
            return;
        }
        Assert::fileExists($autoloadFile, sprintf('Extra autoload file %s was not found', $autoloadFile));
        require_once $autoloadFile;
    }
    public function autoloadPaths(): void
    {
        $autoloadPaths = SimpleParameterProvider::provideArrayParameter(Option::AUTOLOAD_PATHS);
        $autoloadPaths = $this->dynamicSourceLocatorDecorator->addPaths($autoloadPaths);
        // set values of Option::AUTOLOAD_PATHS with transformed paths
        SimpleParameterProvider::setParameter(Option::AUTOLOAD_PATHS, $autoloadPaths);
    }
}
