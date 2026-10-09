<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\NullsafePropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticPropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Trait_;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\ThisType;
use Flames\Code\Upgrade\Enum\ObjectReference;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeNestingScope\ContextAnalyzer;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\PhpParser\AstResolver;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\ValueObject\MethodName;
final readonly class PropertyFetchAnalyzer
{
    private const string THIS = 'this';
    public function __construct(private NodeNameResolver $nodeNameResolver, private BetterNodeFinder $betterNodeFinder, private AstResolver $astResolver, private NodeTypeResolver $nodeTypeResolver, private ReflectionResolver $reflectionResolver, private ContextAnalyzer $contextAnalyzer)
    {
    }
    public function isLocalPropertyFetch(Node $node): bool
    {
        if (!$node instanceof PropertyFetch && !$node instanceof StaticPropertyFetch && !$node instanceof NullsafePropertyFetch) {
            return \false;
        }
        $variableType = $node instanceof StaticPropertyFetch ? $this->nodeTypeResolver->getType($node->class) : $this->nodeTypeResolver->getType($node->var);
        // patch clone usage
        // @see https://github.com/phpstan/phpstan-src/commit/020adb548011c098cdb2e061019346b0a838c6a4
        // @see https://github.com/rectorphp/rector-src/pull/7622
        if ($variableType instanceof StaticType && !$variableType instanceof ThisType) {
            $variableType = $variableType->getStaticObjectType();
        }
        if ($variableType instanceof ObjectType) {
            $classReflection = $this->reflectionResolver->resolveClassReflection($node);
            if ($classReflection instanceof ClassReflection) {
                return $classReflection->getName() === $variableType->getClassName();
            }
            return \false;
        }
        if (!$variableType instanceof ThisType) {
            return $this->isTraitLocalPropertyFetch($node);
        }
        return \true;
    }
    public function isLocalPropertyFetchName(Node $node, string $desiredPropertyName): bool
    {
        if (!$node instanceof PropertyFetch && !$node instanceof StaticPropertyFetch && !$node instanceof NullsafePropertyFetch) {
            return \false;
        }
        if (!$this->nodeNameResolver->isName($node->name, $desiredPropertyName)) {
            return \false;
        }
        return $this->isLocalPropertyFetch($node);
    }
    public function containsLocalPropertyFetchName(Trait_ $trait, string $propertyName): bool
    {
        if ($trait->getProperty($propertyName) instanceof Property) {
            return \true;
        }
        return (bool) $this->betterNodeFinder->findFirst($trait, fn(Node $node): bool => $this->isLocalPropertyFetchName($node, $propertyName));
    }
    public function containsWrittenPropertyFetchName(Trait_ $trait, string $propertyName): bool
    {
        if ($trait->getProperty($propertyName) instanceof Property) {
            return \true;
        }
        return (bool) $this->betterNodeFinder->findFirst($trait, function (Node $node) use ($propertyName): bool {
            if (!$this->isLocalPropertyFetchName($node, $propertyName)) {
                return \false;
            }
            /**
             * @var PropertyFetch|StaticPropertyFetch|NullsafePropertyFetch $node
             */
            if ($this->contextAnalyzer->isChangeableContext($node)) {
                return \true;
            }
            return $this->contextAnalyzer->isLeftPartOfAssign($node);
        });
    }
    /**
     * @phpstan-assert-if-true PropertyFetch|StaticPropertyFetch $node
     */
    public function isPropertyFetch(Node $node): bool
    {
        if ($node instanceof PropertyFetch) {
            return \true;
        }
        return $node instanceof StaticPropertyFetch;
    }
    /**
     * Matches:
     * "$this->someValue = $<variableName>;"
     */
    public function isVariableAssignToThisPropertyFetch(Assign $assign, string $variableName): bool
    {
        if (!$assign->expr instanceof Variable) {
            return \false;
        }
        if (!$this->nodeNameResolver->isName($assign->expr, $variableName)) {
            return \false;
        }
        return $this->isLocalPropertyFetch($assign->var);
    }
    public function isFilledViaMethodCallInConstructStmts(ClassLike $classLike, string $propertyName): bool
    {
        $classMethod = $classLike->getMethod(MethodName::CONSTRUCT);
        if (!$classMethod instanceof ClassMethod) {
            return \false;
        }
        $className = (string) $this->nodeNameResolver->getName($classLike);
        $stmts = (array) $classMethod->stmts;
        foreach ($stmts as $stmt) {
            if (!$stmt instanceof Expression) {
                continue;
            }
            if (!$stmt->expr instanceof MethodCall && !$stmt->expr instanceof StaticCall) {
                continue;
            }
            $callerClassMethod = $this->astResolver->resolveClassMethodFromCall($stmt->expr);
            if (!$callerClassMethod instanceof ClassMethod) {
                continue;
            }
            $callerClassReflection = $this->reflectionResolver->resolveClassReflection($callerClassMethod);
            if (!$callerClassReflection instanceof ClassReflection) {
                continue;
            }
            if (!$callerClassReflection->isClass()) {
                continue;
            }
            $callerClassName = $callerClassReflection->getName();
            $isFound = $this->isPropertyAssignFoundInClassMethod($classLike, $className, $callerClassName, $callerClassMethod, $propertyName);
            if ($isFound) {
                return \true;
            }
        }
        return \false;
    }
    private function isTraitLocalPropertyFetch(Node $node): bool
    {
        if ($node instanceof PropertyFetch) {
            if (!$node->var instanceof Variable) {
                return \false;
            }
            return $this->nodeNameResolver->isName($node->var, self::THIS);
        }
        if ($node instanceof StaticPropertyFetch) {
            if (!$node->class instanceof Name) {
                return \false;
            }
            return $this->nodeNameResolver->isNames($node->class, [ObjectReference::SELF, ObjectReference::STATIC]);
        }
        return \false;
    }
    private function isPropertyAssignFoundInClassMethod(ClassLike $classLike, string $className, string $callerClassName, ClassMethod $classMethod, string $propertyName): bool
    {
        if ($className !== $callerClassName && !$classLike instanceof Trait_) {
            $objectType = new ObjectType($className);
            $callerObjectType = new ObjectType($callerClassName);
            if (!$callerObjectType->isSuperTypeOf($objectType)->yes()) {
                return \false;
            }
        }
        foreach ((array) $classMethod->stmts as $stmt) {
            if (!$stmt instanceof Expression) {
                continue;
            }
            if (!$stmt->expr instanceof Assign) {
                continue;
            }
            if ($this->isLocalPropertyFetchName($stmt->expr->var, $propertyName)) {
                return \true;
            }
        }
        return \false;
    }
}
