<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Reflection;

use PhpParser\Node;
use PHPStan\Reflection\ClassReflection;
final readonly class ClassModifierChecker
{
    public function __construct(private \Flames\Code\Upgrade\Reflection\ReflectionResolver $reflectionResolver)
    {
    }
    public function isInsideFinalClass(Node $node): bool
    {
        $classReflection = $this->reflectionResolver->resolveClassReflection($node);
        if (!$classReflection instanceof ClassReflection) {
            return \false;
        }
        return $classReflection->isFinalByKeyword();
    }
}
