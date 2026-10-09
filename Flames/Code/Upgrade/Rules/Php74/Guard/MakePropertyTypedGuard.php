<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php74\Guard;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use PHPStan\Reflection\ClassReflection;
final readonly class MakePropertyTypedGuard
{
    public function __construct(private \Flames\Code\Upgrade\Rules\Php74\Guard\PropertyTypeChangeGuard $propertyTypeChangeGuard)
    {
    }
    public function isLegal(Property $property, ClassReflection $classReflection, bool $inlinePublic = \true): bool
    {
        if ($property->type instanceof Node) {
            return \false;
        }
        return $this->propertyTypeChangeGuard->isLegal($property, $classReflection, $inlinePublic);
    }
}
