<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
final class ClassLikeAnalyzer
{
    /**
     * @return string[]
     */
    public function resolvePropertyNames(Class_ $class): array
    {
        $propertyNames = [];
        foreach ($class->getProperties() as $property) {
            foreach ($property->props as $prop) {
                $propertyNames[] = $prop->name->toString();
            }
        }
        return $propertyNames;
    }
}
