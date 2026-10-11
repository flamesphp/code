<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Skipper\SkipCriteriaResolver;

use Flames\Code\Upgrade\Configuration\Option;
use Flames\Code\Upgrade\Configuration\Parameter\SimpleParameterProvider;
use Flames\Code\Upgrade\FileSystem\FilePathHelper;
use Flames\Code\Upgrade\Testing\PHPUnit\StaticPHPUnitEnvironment;
/**
 * @see \Flames\Code\Upgrade\Tests\Skipper\SkipCriteriaResolver\SkippedPathsResolver\SkippedPathsResolverTest
 */
final class SkippedPathsResolver
{
    /**
     * @var null|string[]
     */
    private $skippedPaths = null;
    public function __construct(private readonly FilePathHelper $filePathHelper)
    {
    }
    /**
     * @return string[]
     */
    public function resolve(): array
    {
        // disable cache in tests
        if (StaticPHPUnitEnvironment::isPHPUnitRun()) {
            $this->skippedPaths = null;
        }
        // already cached, even only empty array
        if ($this->skippedPaths !== null) {
            return $this->skippedPaths;
        }
        $skip = SimpleParameterProvider::provideArrayParameter(Option::SKIP);
        $this->skippedPaths = [];
        foreach ($skip as $key => $value) {
            if (!is_int($key)) {
                continue;
            }
            if (str_contains((string) $value, '*')) {
                $this->skippedPaths[] = $this->filePathHelper->normalizePathAndSchema($value);
                continue;
            }
            if (file_exists($value)) {
                $this->skippedPaths[] = $this->filePathHelper->normalizePathAndSchema($value);
            }
        }
        return $this->skippedPaths;
    }
}
