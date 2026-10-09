<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Privatization\VisibilityGuard;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Reflection\ClassReflection;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
final readonly class ClassMethodVisibilityGuard
{
    public function __construct(private NodeNameResolver $nodeNameResolver)
    {
    }
    public function isClassMethodVisibilityGuardedByParent(ClassMethod $classMethod, ClassReflection $classReflection): bool
    {
        $methodName = $this->nodeNameResolver->getName($classMethod);
        /** @var ClassReflection[] $parentClassReflections */
        $parentClassReflections = array_merge($classReflection->getParents(), $classReflection->getInterfaces());
        foreach ($parentClassReflections as $parentClassReflection) {
            if ($parentClassReflection->hasMethod($methodName)) {
                return \true;
            }
        }
        return \false;
    }
    public function isClassMethodVisibilityGuardedByTrait(ClassMethod $classMethod, ClassReflection $classReflection): bool
    {
        $parentTraitReflections = $this->getLocalAndParentTraitReflections($classReflection);
        $methodName = $this->nodeNameResolver->getName($classMethod);
        $found = array_any($parentTraitReflections, fn($classReflection) => $classReflection->hasMethod($methodName));
        return $found;
    }
    /**
     * @return ClassReflection[]
     */
    private function getLocalAndParentTraitReflections(ClassReflection $classReflection): array
    {
        $traitReflections = $classReflection->getTraits();
        foreach ($classReflection->getParents() as $parentClassReflection) {
            foreach ($parentClassReflection->getTraits() as $parentTraitReflection) {
                $traitReflections[] = $parentTraitReflection;
            }
        }
        return $traitReflections;
    }
}
