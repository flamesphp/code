<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name\FullyQualified;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use PHPStan\Reflection\ClassReflection;
use Flames\Code\Upgrade\NodeAnalyzer\ArgsAnalyzer;
use Flames\Code\Upgrade\NodeAnalyzer\ExprAnalyzer;
use Flames\Code\Upgrade\PhpParser\Node\Value\ValueResolver;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\ClassMethod\RemoveArgumentFromDefaultParentCallRectorTest
 */
final class RemoveArgumentFromDefaultParentCallRector extends AbstractRector
{
    public function __construct(private readonly ReflectionResolver $reflectionResolver, private readonly ArgsAnalyzer $argsAnalyzer, private readonly ValueResolver $valueResolver, private readonly ExprAnalyzer $exprAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove default argument from parent call', [new CodeSample(<<<'CODE_SAMPLE'
class SomeParent
{
    public function __construct(array $param = [])
    {
    }
}

class ChildClass extends SomeParent
{
    public function __construct(string $differentParam)
    {
        init($differentParam);

        parent::__construct([]);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeParent
{
    public function __construct(array $param = [])
    {
    }
}

class ChildClass extends SomeParent
{
    public function __construct(string $differentParam)
    {
        init($differentParam);

        parent::__construct();
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
    public function refactor(Node $node): ?Class_
    {
        if (!$node->extends instanceof FullyQualified) {
            return null;
        }
        $classReflection = $this->reflectionResolver->resolveClassReflection($node);
        if (!$classReflection instanceof ClassReflection) {
            return null;
        }
        $ancestors = array_filter($classReflection->getAncestors(), fn(ClassReflection $ancestorClassReflection): bool => $ancestorClassReflection->getName() !== $classReflection->getName());
        $hasChanged = \false;
        foreach ($node->getMethods() as $classMethod) {
            if ($classMethod->isPrivate()) {
                continue;
            }
            if ($classMethod->isAbstract()) {
                continue;
            }
            foreach ((array) $classMethod->stmts as $stmt) {
                if (!$stmt instanceof Expression) {
                    continue;
                }
                if (!$stmt->expr instanceof StaticCall) {
                    continue;
                }
                if (!$this->isName($stmt->expr->class, 'parent')) {
                    continue;
                }
                if ($stmt->expr->isFirstClassCallable()) {
                    continue;
                }
                $args = $stmt->expr->getArgs();
                if ($args === []) {
                    continue;
                }
                if ($this->argsAnalyzer->hasNamedArg($args)) {
                    continue;
                }
                $methodName = $this->getName($stmt->expr->name);
                if ($methodName === null) {
                    continue;
                }
                foreach ($ancestors as $ancestor) {
                    $nativeClassReflection = $ancestor->getNativeReflection();
                    if (!$nativeClassReflection->hasMethod($methodName)) {
                        continue;
                    }
                    $method = $nativeClassReflection->getMethod($methodName);
                    $parameters = $method->getParameters();
                    $totalParameters = count($parameters);
                    $justChanged = \false;
                    for ($index = $totalParameters - 1; $index >= 0; --$index) {
                        if (!$parameters[$index]->isDefaultValueAvailable()) {
                            break;
                        }
                        // already passed
                        if (!isset($args[$index])) {
                            break;
                        }
                        // only literal values
                        if ($this->exprAnalyzer->isDynamicExpr($args[$index]->value)) {
                            break;
                        }
                        // on decrement loop, when next arg is not removed, then current can't be removed
                        if (isset($args[$index + 1])) {
                            break;
                        }
                        $defaultValue = $parameters[$index]->getDefaultValue();
                        if ($defaultValue === $this->valueResolver->getValue($args[$index]->value)) {
                            unset($args[$index]);
                            $hasChanged = \true;
                            $justChanged = \true;
                        }
                    }
                    if ($justChanged) {
                        $stmt->expr->args = array_values($args);
                    }
                    break;
                }
            }
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
}
