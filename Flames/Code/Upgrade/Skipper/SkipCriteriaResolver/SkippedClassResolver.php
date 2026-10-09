<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Skipper\SkipCriteriaResolver;

use Flames\Code\Upgrade\Configuration\Deprecation\Contract\DeprecatedInterface;
use Flames\Code\Upgrade\Configuration\Option;
use Flames\Code\Upgrade\Configuration\Parameter\SimpleParameterProvider;
use Flames\Code\Upgrade\Testing\PHPUnit\StaticPHPUnitEnvironment;
/**
 * @see \Flames\Code\Upgrade\Tests\Skipper\Skipper\SkippedClassResolverTest
 */
final class SkippedClassResolver
{
    /**
     * @var null|array<class-string, string[]|null>
     */
    private $skippedClassesToFiles = null;
    /**
     * @return array<class-string<DeprecatedInterface>>
     */
    public function resolveDeprecatedSkippedClasses(): array
    {
        $skippedClassNames = array_keys($this->resolve());
        return array_filter($skippedClassNames, fn(string $class): bool => is_a($class, DeprecatedInterface::class, \true));
    }
    /**
     * @return array<class-string, string[]|null>
     */
    public function resolve(): array
    {
        // disable cache in tests
        if (StaticPHPUnitEnvironment::isPHPUnitRun()) {
            $this->skippedClassesToFiles = null;
        }
        // already cached, even only empty array
        if ($this->skippedClassesToFiles !== null) {
            return $this->skippedClassesToFiles;
        }
        $skip = SimpleParameterProvider::provideArrayParameter(Option::SKIP);
        $this->skippedClassesToFiles = [];
        foreach ($skip as $key => $value) {
            // e.g. [SomeClass::class] → shift values to [SomeClass::class => null]
            if (is_int($key)) {
                $key = $value;
                $value = null;
            }
            if (!is_string($key)) {
                continue;
            }
            // this only checks for Upgrade rules, that are always autoloaded
            if (!class_exists($key) && !interface_exists($key)) {
                continue;
            }
            $this->skippedClassesToFiles[$key] = $value;
        }
        return $this->skippedClassesToFiles;
    }
}
