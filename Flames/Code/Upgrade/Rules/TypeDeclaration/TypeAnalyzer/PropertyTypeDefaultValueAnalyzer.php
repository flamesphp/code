<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\TypeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\PropertyItem;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
final readonly class PropertyTypeDefaultValueAnalyzer
{
    public function __construct(private StaticTypeMapper $staticTypeMapper)
    {
    }
    public function doesConflictWithDefaultValue(PropertyItem $propertyItem, Type $propertyType): bool
    {
        if (!$propertyItem->default instanceof Expr) {
            return \false;
        }
        // the defaults can be in conflict
        $defaultType = $this->staticTypeMapper->mapPhpParserNodePHPStanType($propertyItem->default);
        if ($defaultType->isArray()->yes() && $propertyType->isArray()->yes()) {
            return \false;
        }
        // type is not matching, skip it
        return !$defaultType->isSuperTypeOf($propertyType)->yes();
    }
}
