<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\AlreadyAssignDetector;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
final class PropertyDefaultAssignDetector
{
    public function detect(ClassLike $classLike, string $propertyName): bool
    {
        $property = $classLike->getProperty($propertyName);
        if (!$property instanceof Property) {
            return \false;
        }
        return $property->props[0]->default instanceof Expr;
    }
}
