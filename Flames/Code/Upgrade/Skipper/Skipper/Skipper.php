<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Skipper\Skipper;

use PhpParser\Node;
use PHPStan\Reflection\ReflectionProvider;
use Flames\Code\Upgrade\Contract\Rector\RectorInterface;
use Flames\Code\Upgrade\ProcessAnalyzer\RectifiedAnalyzer;
use Flames\Code\Upgrade\Skipper\SkipCriteriaResolver\SkippedClassResolver;
use Flames\Code\Upgrade\Skipper\ValueObject\SkipMatch;
/**
 * @api
 * @see \Flames\Code\Upgrade\Tests\Skipper\Skipper\SkipperTest
 */
final readonly class Skipper
{
    public function __construct(private RectifiedAnalyzer $rectifiedAnalyzer, private \Flames\Code\Upgrade\Skipper\Skipper\PathSkipper $pathSkipper, private \Flames\Code\Upgrade\Skipper\Skipper\SkipSkipper $skipSkipper, private SkippedClassResolver $skippedClassResolver, private ReflectionProvider $reflectionProvider, private \Flames\Code\Upgrade\Skipper\Skipper\UsedSkipCollector $usedSkipCollector)
    {
    }
    /**
     * @param string|object $element
     */
    public function shouldSkipElement($element): bool
    {
        return $this->shouldSkipElementAndFilePath($element, __FILE__);
    }
    public function shouldSkipFilePath(string $filePath): bool
    {
        return $this->pathSkipper->shouldSkip($filePath);
    }
    /**
     * @param string|object $element
     */
    public function shouldSkipElementAndFilePath($element, string $filePath): bool
    {
        $skipMatch = $this->matchSkip($element, $filePath);
        if (!$skipMatch instanceof SkipMatch) {
            return \false;
        }
        $this->markSkipUsed($skipMatch);
        return \true;
    }
    /**
     * Match a class/path skip without marking it used. Callers that can only tell whether the skip
     * actually prevented a change later on must mark it used themselves via markSkipUsed().
     * @param string|object $element
     */
    public function matchSkip($element, string $filePath): ?SkipMatch
    {
        if (!is_object($element) && !$this->reflectionProvider->hasClass($element)) {
            return null;
        }
        return $this->skipSkipper->match($element, $filePath, $this->skippedClassResolver->resolve());
    }
    public function markSkipUsed(SkipMatch $skipMatch): void
    {
        $this->usedSkipCollector->markUsed($skipMatch->getSkippedClass(), $skipMatch->getMatchedPath());
    }
    /**
     * @param class-string<RectorInterface> $rectorClass
     */
    public function shouldSkipCurrentNode(string $rectorClass, Node $node): bool
    {
        return $this->rectifiedAnalyzer->hasRectified($rectorClass, $node);
    }
}
