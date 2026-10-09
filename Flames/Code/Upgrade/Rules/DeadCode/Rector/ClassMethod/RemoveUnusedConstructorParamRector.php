<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Comment\Doc;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Reflection\ClassReflection;
use Flames\Code\Upgrade\Rules\DeadCode\NodeManipulator\ClassMethodParamRemover;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveUnusedConstructorParamRectorTest
 */
final class RemoveUnusedConstructorParamRector extends AbstractRector
{
    public function __construct(private readonly ReflectionResolver $reflectionResolver, private readonly ClassMethodParamRemover $classMethodParamRemover)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove unused parameter in constructor', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    private $hey;

    public function __construct($hey, $man)
    {
        $this->hey = $hey;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    private $hey;

    public function __construct($hey)
    {
        $this->hey = $hey;
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
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $constructorClassMethod = $node->getMethod(MethodName::CONSTRUCT);
        if (!$constructorClassMethod instanceof ClassMethod) {
            return null;
        }
        if ($constructorClassMethod->params === []) {
            return null;
        }
        if ($constructorClassMethod->isAbstract()) {
            return null;
        }
        $classReflection = $this->reflectionResolver->resolveClassReflection($node);
        if (!$classReflection instanceof ClassReflection) {
            return null;
        }
        $interfaces = $classReflection->getInterfaces();
        foreach ($interfaces as $interface) {
            // parameters are contract required → skip
            if ($interface->hasNativeMethod(MethodName::CONSTRUCT)) {
                return null;
            }
        }
        // attributes can be used as markers
        if ($classReflection->isAttributeClass() && $constructorClassMethod->getDocComment() instanceof Doc) {
            return null;
        }
        $changedConstructorClassMethod = $this->classMethodParamRemover->processRemoveParams($constructorClassMethod);
        if (!$changedConstructorClassMethod instanceof ClassMethod) {
            return null;
        }
        return $node;
    }
}
