<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Doctrine\TypedCollections\NodeModifier;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\Property;
final class PropertyDefaultNullRemover
{
    public function remove(Property $property): void
    {
        $soleProperty = $property->props[0];
        if (!$soleProperty->default instanceof Expr) {
            return;
        }
        $soleProperty->default = null;
    }
}
