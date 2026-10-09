<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeTypeResolver\TypeComparator;

use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\MixedType;
/**
 * @see \Flames\Code\Upgrade\Tests\NodeTypeResolver\TypeComparator\ArrayTypeComparatorTest
 */
final class ArrayTypeComparator
{
    /**
     * @param \PHPStan\Type\ArrayType|\PHPStan\Type\Constant\ConstantArrayType $checkedType
     * @param \PHPStan\Type\ArrayType|\PHPStan\Type\Constant\ConstantArrayType $mainType
     */
    public function isSubtype($checkedType, $mainType): bool
    {
        if (!$checkedType instanceof ConstantArrayType && !$mainType instanceof ConstantArrayType) {
            return $mainType->isSuperTypeOf($checkedType)->yes();
        }
        $checkedKeyType = $checkedType->getIterableKeyType();
        $mainKeyType = $mainType->getIterableKeyType();
        if (!$mainKeyType instanceof MixedType && $mainKeyType->isSuperTypeOf($checkedKeyType)->yes()) {
            return \true;
        }
        $checkedItemType = $checkedType->getIterableValueType();
        $mainItemType = $mainType->getIterableValueType();
        return $checkedItemType->isSuperTypeOf($mainItemType)->yes();
    }
}
