<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\AlreadyAssignDetector;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Property;
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
