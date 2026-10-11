<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeManipulator;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Reflection\ClassReflection;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\ValueObject\MethodName;
final readonly class ClassMethodManipulator
{
    public function __construct(private NodeNameResolver $nodeNameResolver, private ReflectionResolver $reflectionResolver)
    {
    }
    public function isNamedConstructor(ClassMethod $classMethod): bool
    {
        if (!$this->nodeNameResolver->isName($classMethod, MethodName::CONSTRUCT)) {
            return \false;
        }
        $classReflection = $this->reflectionResolver->resolveClassReflection($classMethod);
        if (!$classReflection instanceof ClassReflection) {
            return \false;
        }
        if ($classMethod->isPrivate()) {
            return \true;
        }
        if ($classReflection->isFinalByKeyword()) {
            return \false;
        }
        return $classMethod->isProtected();
    }
    public function hasParentMethodOrInterfaceMethod(Class_ $class, string $methodName): bool
    {
        $classReflection = $this->reflectionResolver->resolveClassReflection($class);
        if (!$classReflection instanceof ClassReflection) {
            return \false;
        }
        foreach ($classReflection->getParents() as $parentClassReflection) {
            if ($parentClassReflection->hasMethod($methodName)) {
                return \true;
            }
            if ($parentClassReflection->hasMethod(MethodName::CALL)) {
                return \true;
            }
        }
        $found = array_any($classReflection->getInterfaces(), fn($classReflection) => $classReflection->hasMethod($methodName));
        return $found;
    }
}
