<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Privatization\Rector\Property;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use PHPStan\Reflection\ClassReflection;
use Flames\Code\Upgrade\Rules\Privatization\Guard\ParentPropertyLookupGuard;
use Flames\Code\Upgrade\Rules\Privatization\NodeManipulator\VisibilityManipulator;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Flames\Code\Upgrade\ValueObject\Visibility;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Privatization\Rector\Property\PrivatizeFinalClassPropertyRectorTest
 */
final class PrivatizeFinalClassPropertyRector extends AbstractRector
{
    public function __construct(private readonly VisibilityManipulator $visibilityManipulator, private readonly ParentPropertyLookupGuard $parentPropertyLookupGuard, private readonly ReflectionResolver $reflectionResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change property to private if possible', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    protected $value;
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    private $value;
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node->isFinal()) {
            return null;
        }
        $hasChanged = \false;
        $classReflection = $this->reflectionResolver->resolveClassReflection($node);
        if (!$classReflection instanceof ClassReflection) {
            return null;
        }
        foreach ($node->getProperties() as $property) {
            if ($this->shouldSkipProperty($property)) {
                continue;
            }
            if (!$this->parentPropertyLookupGuard->isLegal($property, $classReflection)) {
                continue;
            }
            $this->visibilityManipulator->makePrivate($property);
            $hasChanged = \true;
        }
        $construct = $node->getMethod(MethodName::CONSTRUCT);
        if ($construct instanceof ClassMethod) {
            foreach ($construct->params as $param) {
                if (!$param->isPromoted()) {
                    continue;
                }
                if (!$this->visibilityManipulator->hasVisibility($param, Visibility::PROTECTED)) {
                    continue;
                }
                if (!$this->parentPropertyLookupGuard->isLegal((string) $this->getName($param), $classReflection)) {
                    continue;
                }
                $this->visibilityManipulator->makePrivate($param);
                $hasChanged = \true;
            }
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    private function shouldSkipProperty(Property $property): bool
    {
        if (count($property->props) !== 1) {
            return \true;
        }
        return !$property->isProtected();
    }
}
