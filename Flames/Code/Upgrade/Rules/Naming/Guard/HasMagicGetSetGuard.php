<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Naming\Guard;

use PHPStan\Reflection\ReflectionProvider;
use Flames\Code\Upgrade\Rules\Naming\ValueObject\PropertyRename;
final readonly class HasMagicGetSetGuard
{
    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }
    public function isConflicting(PropertyRename $propertyRename): bool
    {
        if (!$this->reflectionProvider->hasClass($propertyRename->getClassLikeName())) {
            return \false;
        }
        $classReflection = $this->reflectionProvider->getClass($propertyRename->getClassLikeName());
        if ($classReflection->hasMethod('__set')) {
            return \true;
        }
        return $classReflection->hasMethod('__get');
    }
}
