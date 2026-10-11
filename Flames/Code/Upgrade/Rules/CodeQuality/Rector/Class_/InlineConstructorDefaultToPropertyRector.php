<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use PHPStan\Reflection\ClassReflection;
use Flames\Code\Upgrade\NodeAnalyzer\ExprAnalyzer;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PhpParser\NodeFinder\PropertyFetchFinder;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\ValueObject\MethodName;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRectorTest
 */
final class InlineConstructorDefaultToPropertyRector extends AbstractRector
{
    public function __construct(private readonly ExprAnalyzer $exprAnalyzer, private readonly BetterNodeFinder $betterNodeFinder, private readonly PropertyFetchFinder $propertyFetchFinder, private readonly ReflectionResolver $reflectionResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Move property default from constructor to property default', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    private $name;

    public function __construct()
    {
        $this->name = 'John';
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    private $name = 'John';

    public function __construct()
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
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        // A child class may bypass the constructor and depend on the declared property default
        if (!$node->isFinal()) {
            return null;
        }
        $hasChanged = \false;
        $constructClassMethod = $node->getMethod(MethodName::CONSTRUCT);
        if (!$constructClassMethod instanceof ClassMethod) {
            return null;
        }
        if ($constructClassMethod->stmts === null) {
            return null;
        }
        $hasParentConstructCall = \false;
        foreach ($constructClassMethod->stmts as $key => $stmt) {
            // code that is possibly breaking flow
            if ($stmt instanceof If_) {
                return null;
            }
            if (!$stmt instanceof Expression) {
                continue;
            }
            if ($this->isParentConstructCall($stmt->expr)) {
                $hasParentConstructCall = \true;
                continue;
            }
            if (!$stmt->expr instanceof Assign) {
                continue;
            }
            $assign = $stmt->expr;
            $propertyName = $this->matchAssignedLocalPropertyName($assign);
            if (!is_string($propertyName)) {
                continue;
            }
            $defaultExpr = $assign->expr;
            if ($this->exprAnalyzer->isDynamicExpr($defaultExpr)) {
                continue;
            }
            // parent constructor may set the very same property, keep assign order as is
            if ($hasParentConstructCall && $this->isPropertyDefinedInParentClass($node, $propertyName)) {
                continue;
            }
            $hasPropertyChanged = $this->refactorProperty($node, $propertyName, $defaultExpr, $constructClassMethod, $key);
            if ($hasPropertyChanged) {
                $hasChanged = \true;
            }
        }
        if (!$hasChanged) {
            return null;
        }
        return $node;
    }
    private function isParentConstructCall(Expr $expr): bool
    {
        if (!$expr instanceof StaticCall) {
            return \false;
        }
        if (!$this->isName($expr->class, 'parent')) {
            return \false;
        }
        return $this->isName($expr->name, MethodName::CONSTRUCT);
    }
    private function isPropertyDefinedInParentClass(Class_ $class, string $propertyName): bool
    {
        $classReflection = $this->reflectionResolver->resolveClassReflection($class);
        if (!$classReflection instanceof ClassReflection) {
            // unknown parent, keep it safe
            return \true;
        }
        $found = array_any($classReflection->getParents(), fn($parentClassReflection) => $parentClassReflection->getNativeReflection()->hasProperty($propertyName));
        return $found;
    }
    private function matchAssignedLocalPropertyName(Assign $assign): ?string
    {
        if (!$assign->var instanceof PropertyFetch) {
            return null;
        }
        $propertyFetch = $assign->var;
        if (!$this->isName($propertyFetch->var, 'this')) {
            return null;
        }
        $propertyName = $this->getName($propertyFetch->name);
        if (!is_string($propertyName)) {
            return null;
        }
        return $propertyName;
    }
    private function isFoundInAnyPropertyHooks(Class_ $class, string $propertyName): bool
    {
        $propertyHooks = array_reduce($class->getProperties(), static fn(array $hooks, Property $property): array => array_merge($hooks, $property->hooks), []);
        return (bool) $this->betterNodeFinder->findFirst($propertyHooks, function (Node $subNode) use ($class, $propertyName): bool {
            if (!$subNode instanceof PropertyFetch) {
                return \false;
            }
            return $this->propertyFetchFinder->isLocalPropertyFetchByName($subNode, $class, $propertyName);
        });
    }
    private function refactorProperty(Class_ $class, string $propertyName, Expr $defaultExpr, ClassMethod $constructClassMethod, int $key): bool
    {
        if ($class->isReadonly()) {
            return \false;
        }
        if ($this->isFoundInAnyPropertyHooks($class, $propertyName)) {
            return \false;
        }
        foreach ($class->stmts as $classStmt) {
            if (!$classStmt instanceof Property) {
                continue;
            }
            // readonly property cannot have default value
            if ($classStmt->isReadonly()) {
                continue;
            }
            foreach ($classStmt->props as $propertyProperty) {
                if (!$this->isName($propertyProperty, $propertyName)) {
                    continue;
                }
                $propertyProperty->default = $defaultExpr;
                $classStmt->setAttribute(AttributeKey::COMMENTS, array_merge($classStmt->getComments(), isset($constructClassMethod->stmts[$key]) ? $constructClassMethod->stmts[$key]->getComments() : []));
                // remove assign
                unset($constructClassMethod->stmts[$key]);
                return \true;
            }
        }
        return \false;
    }
}
