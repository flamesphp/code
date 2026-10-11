<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc;

use PhpParser\Parser;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Psr\Psr4Mapping;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedPsrAutoloaderLocatorFactory;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository;
use function dirname;
final class StubSourceLocatorFactory
{
    private Parser $php8Parser;
    private PhpStormStubsSourceStubber $phpStormStubsSourceStubber;
    private OptimizedSingleFileSourceLocatorRepository $optimizedSingleFileSourceLocatorRepository;
    private OptimizedPsrAutoloaderLocatorFactory $optimizedPsrAutoloaderLocatorFactory;
    /**
     * @var string[]
     */
    private array $allStubFiles;
    /**
     * @param string[] $allStubFiles
     */
    public function __construct(Parser $php8Parser, PhpStormStubsSourceStubber $phpStormStubsSourceStubber, OptimizedSingleFileSourceLocatorRepository $optimizedSingleFileSourceLocatorRepository, OptimizedPsrAutoloaderLocatorFactory $optimizedPsrAutoloaderLocatorFactory, array $allStubFiles)
    {
        $this->php8Parser = $php8Parser;
        $this->phpStormStubsSourceStubber = $phpStormStubsSourceStubber;
        $this->optimizedSingleFileSourceLocatorRepository = $optimizedSingleFileSourceLocatorRepository;
        $this->optimizedPsrAutoloaderLocatorFactory = $optimizedPsrAutoloaderLocatorFactory;
        $this->allStubFiles = $allStubFiles;
    }
    public function create(): SourceLocator
    {
        $locators = [];
        $astPhp8Locator = new Locator($this->php8Parser);
        foreach ($this->allStubFiles as $stubFile) {
            $locators[] = $this->optimizedSingleFileSourceLocatorRepository->getOrCreate($stubFile);
        }
        $locators[] = $this->optimizedPsrAutoloaderLocatorFactory->create(Psr4Mapping::fromArrayMappings(['PHPStan\\' => [dirname(__DIR__) . '/']]));
        $locators[] = $this->optimizedPsrAutoloaderLocatorFactory->create(Psr4Mapping::fromArrayMappings(['PhpParser\\' => [dirname(__DIR__, 4) . '/PhpParser/']]));
        $locators[] = new PhpInternalSourceLocator($astPhp8Locator, $this->phpStormStubsSourceStubber);
        // no MemoizingSourceLocator here - located reflections are already memoized
        // by MemoizingReflector, a second cache layer would only pin them in memory twice
        return new AggregateSourceLocator($locators);
    }
}
