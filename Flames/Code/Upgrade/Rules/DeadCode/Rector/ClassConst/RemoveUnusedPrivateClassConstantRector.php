<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassConst;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassConst;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor;
use PHPStan\Reflection\ClassReflection;
use Flames\Code\Upgrade\NodeManipulator\ClassConstManipulator;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassConst\RemoveUnusedPrivateClassConstantRectorTest
 */
final class RemoveUnusedPrivateClassConstantRector extends AbstractRector
{
    public function __construct(private readonly ClassConstManipulator $classConstManipulator, private readonly ReflectionResolver $reflectionResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove unused class constants', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    private const SOME_CONST = 'dead';

    public function run()
    {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassConst::class];
    }
    /**
     * @param ClassConst $node
     */
    public function refactor(Node $node): ?int
    {
        if ($this->shouldSkipClassConst($node)) {
            return null;
        }
        $classReflection = $this->reflectionResolver->resolveClassReflection($node);
        if (!$classReflection instanceof ClassReflection) {
            return null;
        }
        if ($this->classConstManipulator->hasClassConstFetch($node, $classReflection)) {
            return null;
        }
        return NodeVisitor::REMOVE_NODE;
    }
    private function shouldSkipClassConst(ClassConst $classConst): bool
    {
        if (!$classConst->isPrivate()) {
            return \true;
        }
        if (count($classConst->consts) !== 1) {
            return \true;
        }
        $scope = ScopeFetcher::fetch($classConst);
        $classReflection = $scope->getClassReflection();
        if (!$classReflection instanceof ClassReflection) {
            return \false;
        }
        return $this->hasParentClassOfEnumSuffix($classReflection);
    }
    private function hasParentClassOfEnumSuffix(ClassReflection $classReflection): bool
    {
        $found = array_any($classReflection->getParentClassesNames(), fn($parentClassesName) => str_ends_with($parentClassesName, 'Enum'));
        return $found;
    }
}
