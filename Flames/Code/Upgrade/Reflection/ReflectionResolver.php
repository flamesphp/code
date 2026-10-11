<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Reflection;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Attribute;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\CallLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\NullsafeMethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticPropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Php\PhpPropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\TypeCombinator;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\NodeAnalyzer\ClassAnalyzer;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\StaticTypeMapper\Resolver\ClassNameFromObjectTypeResolver;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\AliasedObjectType;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\ShortenedObjectType;
use Flames\Code\Upgrade\ValueObject\MethodName;
final readonly class ReflectionResolver
{
    public function __construct(private ReflectionProvider $reflectionProvider, private NodeTypeResolver $nodeTypeResolver, private NodeNameResolver $nodeNameResolver, private ClassAnalyzer $classAnalyzer, private \Flames\Code\Upgrade\Reflection\MethodReflectionResolver $methodReflectionResolver)
    {
    }
    /**
     * @api
     */
    public function resolveClassAndAnonymousClass(ClassLike $classLike): ClassReflection
    {
        if ($classLike instanceof Class_ && $this->classAnalyzer->isAnonymousClass($classLike)) {
            $classLikeScope = $classLike->getAttribute(AttributeKey::SCOPE);
            if (!$classLikeScope instanceof Scope) {
                throw new ShouldNotHappenException();
            }
            return $this->reflectionProvider->getAnonymousClassReflection($classLike, $classLikeScope);
        }
        $className = (string) $this->nodeNameResolver->getName($classLike);
        return $this->reflectionProvider->getClass($className);
    }
    public function resolveClassReflection(Node $node): ?ClassReflection
    {
        $scope = $node->getAttribute(AttributeKey::SCOPE);
        if (!$scope instanceof Scope) {
            return null;
        }
        return $scope->getClassReflection();
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\NullsafeMethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticPropertyFetch $node
     */
    public function resolveClassReflectionSourceObject($node): ?ClassReflection
    {
        $objectType = $node instanceof StaticCall || $node instanceof StaticPropertyFetch ? $this->nodeTypeResolver->getType($node->class) : $this->nodeTypeResolver->getType($node->var);
        $className = ClassNameFromObjectTypeResolver::resolve($objectType);
        if ($className === null) {
            return null;
        }
        if (!$this->reflectionProvider->hasClass($className)) {
            return null;
        }
        $classReflection = $this->reflectionProvider->getClass($className);
        if ($node instanceof PropertyFetch || $node instanceof StaticPropertyFetch) {
            $propertyName = (string) $this->nodeNameResolver->getName($node->name);
            if (!$classReflection->hasNativeProperty($propertyName)) {
                return null;
            }
            $property = $classReflection->getNativeProperty($propertyName);
            if ($property->isPrivate()) {
                return $classReflection;
            }
            if ($this->reflectionProvider->hasClass($property->getDeclaringClass()->getName())) {
                return $this->reflectionProvider->getClass($property->getDeclaringClass()->getName());
            }
            return $classReflection;
        }
        $methodName = (string) $this->nodeNameResolver->getName($node->name);
        if (!$classReflection->hasNativeMethod($methodName)) {
            return null;
        }
        $extendedMethodReflection = $classReflection->getNativeMethod($methodName);
        if ($extendedMethodReflection->isPrivate()) {
            return $classReflection;
        }
        if ($this->reflectionProvider->hasClass($extendedMethodReflection->getDeclaringClass()->getName())) {
            return $this->reflectionProvider->getClass($extendedMethodReflection->getDeclaringClass()->getName());
        }
        return $classReflection;
    }
    /**
     * @param class-string $className
     */
    public function resolveMethodReflection(string $className, string $methodName, ?Scope $scope): ?MethodReflection
    {
        return $this->methodReflectionResolver->resolveMethodReflection($className, $methodName, $scope);
    }
    public function resolveMethodReflectionFromStaticCall(StaticCall $staticCall): ?MethodReflection
    {
        $objectType = $this->nodeTypeResolver->getType($staticCall->class);
        if ($objectType instanceof ShortenedObjectType || $objectType instanceof AliasedObjectType) {
            /** @var array<class-string> $classNames */
            $classNames = [$objectType->getFullyQualifiedName()];
        } else {
            /** @var array<class-string> $classNames */
            $classNames = $objectType->getObjectClassNames();
        }
        $methodName = $this->nodeNameResolver->getName($staticCall->name);
        if ($methodName === null) {
            return null;
        }
        $scope = $staticCall->getAttribute(AttributeKey::SCOPE);
        foreach ($classNames as $className) {
            $methodReflection = $this->resolveMethodReflection($className, $methodName, $scope);
            if ($methodReflection instanceof MethodReflection) {
                return $methodReflection;
            }
        }
        return null;
    }
    public function resolveMethodReflectionFromMethodCall(MethodCall $methodCall): ?MethodReflection
    {
        $callerType = $this->nodeTypeResolver->getType($methodCall->var);
        if ($callerType instanceof BenevolentUnionType) {
            $callerType = TypeCombinator::removeFalsey($callerType);
        }
        $className = ClassNameFromObjectTypeResolver::resolve($callerType);
        if ($className === null) {
            return null;
        }
        $methodName = $this->nodeNameResolver->getName($methodCall->name);
        if ($methodName === null) {
            return null;
        }
        $scope = $methodCall->getAttribute(AttributeKey::SCOPE);
        return $this->resolveMethodReflection($className, $methodName, $scope);
    }
    /**
     * @return \PHPStan\Reflection\MethodReflection|\PHPStan\Reflection\FunctionReflection|null
     */
    public function resolveFunctionLikeReflectionFromCall(CallLike $callLike)
    {
        if ($callLike instanceof MethodCall) {
            return $this->resolveMethodReflectionFromMethodCall($callLike);
        }
        if ($callLike instanceof StaticCall) {
            return $this->resolveMethodReflectionFromStaticCall($callLike);
        }
        if ($callLike instanceof New_) {
            return $this->resolveMethodReflectionFromNew($callLike);
        }
        if ($callLike instanceof FuncCall) {
            return $this->resolveFunctionReflectionFromFuncCall($callLike);
        }
        // todo: support NullsafeMethodCall
        return null;
    }
    /**
     * @api used in rector-laravel
     */
    public function resolveMethodReflectionFromClassMethod(ClassMethod $classMethod, Scope $scope): ?MethodReflection
    {
        $classReflection = $scope->getClassReflection();
        if (!$classReflection instanceof ClassReflection) {
            return null;
        }
        $className = $classReflection->getName();
        $methodName = $this->nodeNameResolver->getName($classMethod);
        return $this->resolveMethodReflection($className, $methodName, $scope);
    }
    public function resolveMethodReflectionFromNew(New_ $new): ?MethodReflection
    {
        $newClassType = $this->nodeTypeResolver->getType($new->class);
        $className = ClassNameFromObjectTypeResolver::resolve($newClassType);
        if ($className === null) {
            return null;
        }
        $scope = $new->getAttribute(AttributeKey::SCOPE);
        return $this->resolveMethodReflection($className, MethodName::CONSTRUCT, $scope);
    }
    public function resolveConstructorReflectionFromAttribute(Attribute $attribute): ?MethodReflection
    {
        $attributeClassType = $this->nodeTypeResolver->getType($attribute->name);
        $className = ClassNameFromObjectTypeResolver::resolve($attributeClassType);
        if ($className === null) {
            return null;
        }
        $scope = $attribute->getAttribute(AttributeKey::SCOPE);
        return $this->resolveMethodReflection($className, MethodName::CONSTRUCT, $scope);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticPropertyFetch $propertyFetch
     */
    public function resolvePropertyReflectionFromPropertyFetch($propertyFetch): ?PhpPropertyReflection
    {
        $propertyName = $this->nodeNameResolver->getName($propertyFetch->name);
        if ($propertyName === null) {
            return null;
        }
        $fetcheeType = $propertyFetch instanceof PropertyFetch ? $this->nodeTypeResolver->getType($propertyFetch->var) : $this->nodeTypeResolver->getType($propertyFetch->class);
        $className = ClassNameFromObjectTypeResolver::resolve($fetcheeType);
        if ($className === null) {
            return null;
        }
        if (!$this->reflectionProvider->hasClass($className)) {
            return null;
        }
        $classReflection = $this->reflectionProvider->getClass($className);
        if (!$classReflection->hasNativeProperty($propertyName)) {
            return null;
        }
        return $classReflection->getNativeProperty($propertyName);
    }
    /**
     * @return \PHPStan\Reflection\FunctionReflection|\PHPStan\Reflection\MethodReflection|null
     */
    private function resolveFunctionReflectionFromFuncCall(FuncCall $funcCall)
    {
        if (!$funcCall->name instanceof Name) {
            return null;
        }
        $functionName = new Name((string) $this->nodeNameResolver->getName($funcCall));
        if ($this->reflectionProvider->hasFunction($functionName, null)) {
            return $this->reflectionProvider->getFunction($functionName, null);
        }
        return null;
    }
}
