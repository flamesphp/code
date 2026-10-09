<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassConstFetch;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\ClassConstFetch\VariableConstFetchToClassConstFetchRectorTest
 */
final class VariableConstFetchToClassConstFetchRector extends AbstractRector
{
    public function __construct(private readonly ReflectionProvider $reflectionProvider)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change variable class constant fetch to direct class constant fetch when class or constant target is final', [new CodeSample(<<<'CODE_SAMPLE'
final class AnotherClass
{
}

class SomeClass
{
    public function run(AnotherClass $anotherClass)
    {
        return $anotherClass::CONSTANT_NAME;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class AnotherClass
{
}

class SomeClass
{
    public function run(AnotherClass $anotherClass)
    {
        return AnotherClass::CONSTANT_NAME;
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
        return [ClassConstFetch::class];
    }
    /**
     * @param ClassConstFetch $node
     */
    public function refactor(Node $node): ?ClassConstFetch
    {
        if (!$node->class instanceof Variable) {
            return null;
        }
        if (!$node->name instanceof Identifier) {
            return null;
        }
        $constantName = $this->getName($node->name);
        if (!is_string($constantName)) {
            return null;
        }
        $classObjectType = $this->nodeTypeResolver->getNativeType($node->class);
        if (!$classObjectType instanceof ObjectType) {
            return null;
        }
        if (!$classObjectType->hasConstant($constantName)->yes()) {
            return null;
        }
        if (!$this->reflectionProvider->hasClass($classObjectType->getClassName())) {
            return null;
        }
        $classReflection = $this->reflectionProvider->getClass($classObjectType->getClassName());
        if (!$classReflection->isFinalByKeyword()) {
            if (!$classReflection->hasConstant($constantName)) {
                return null;
            }
            $constant = $classReflection->getConstant($constantName);
            if (!$constant->isFinalByKeyword()) {
                return null;
            }
        }
        $node->class = new FullyQualified($classObjectType->getClassName());
        return $node;
    }
}
