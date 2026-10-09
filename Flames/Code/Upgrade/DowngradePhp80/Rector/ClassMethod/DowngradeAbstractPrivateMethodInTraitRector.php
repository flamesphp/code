<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp80\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Modifiers;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Reflection\ClassReflection;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\DowngradePhp80\Rector\ClassMethod\DowngradeAbstractPrivateMethodInTraitRectorTest
 */
final class DowngradeAbstractPrivateMethodInTraitRector extends AbstractRector
{
    public function __construct(private readonly ReflectionResolver $reflectionResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove "abstract" from private methods in traits and adds an empty function body', [new CodeSample(<<<'CODE_SAMPLE'
trait SomeTrait
{
    abstract private function someAbstractPrivateFunction();
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
trait SomeTrait
{
    private function someAbstractPrivateFunction() {}
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }
    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->shouldSkip($node)) {
            return null;
        }
        // remove abstract
        $node->flags -= Modifiers::ABSTRACT;
        // Add empty array for stmts to generate empty function body
        $node->stmts = [];
        return $node;
    }
    private function shouldSkip(ClassMethod $classMethod): bool
    {
        if (!$classMethod->isAbstract()) {
            return \true;
        }
        if (!$classMethod->isPrivate()) {
            return \true;
        }
        $classReflection = $this->reflectionResolver->resolveClassReflection($classMethod);
        if (!$classReflection instanceof ClassReflection) {
            return \true;
        }
        return !$classReflection->isTrait();
    }
}
