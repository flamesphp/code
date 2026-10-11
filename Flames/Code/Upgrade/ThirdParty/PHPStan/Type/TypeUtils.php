<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Internal\CombinationsHelper;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Generic\TemplateBenevolentUnionType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateUnionType;
use PHPStan\Type\Traverser\LateResolvableTraverser;
use function array_merge;
use function count;
use function max;
use const PHP_INT_MAX;
/**
 * @api
 */
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/TypeUtils.cpp')]
final class TypeUtils
{
    /**
     * @return list<ConstantIntegerType>
     */
    public static function getConstantIntegers(\PHPStan\Type\Type $type): array
    {
        return self::map(ConstantIntegerType::class, $type, \false);
    }
    /**
     * @return list<IntegerRangeType>
     */
    public static function getIntegerRanges(\PHPStan\Type\Type $type): array
    {
        return self::map(\PHPStan\Type\IntegerRangeType::class, $type, \false);
    }
    /**
     * @return list<mixed>
     */
    private static function map(string $typeClass, \PHPStan\Type\Type $type, bool $inspectIntersections, bool $stopOnUnmatched = \true): array
    {
        if ($type instanceof $typeClass) {
            return [$type];
        }
        if ($type instanceof \PHPStan\Type\UnionType) {
            $matchingTypes = [];
            foreach ($type->getTypes() as $innerType) {
                $matchingInner = self::map($typeClass, $innerType, $inspectIntersections, $stopOnUnmatched);
                if ($matchingInner === []) {
                    if ($stopOnUnmatched) {
                        return [];
                    }
                    continue;
                }
                foreach ($matchingInner as $innerMapped) {
                    $matchingTypes[] = $innerMapped;
                }
            }
            return $matchingTypes;
        }
        if ($inspectIntersections && $type instanceof \PHPStan\Type\IntersectionType) {
            $matchingTypes = [];
            foreach ($type->getTypes() as $innerType) {
                if (!$innerType instanceof $typeClass) {
                    if ($stopOnUnmatched) {
                        return [];
                    }
                    continue;
                }
                $matchingTypes[] = $innerType;
            }
            return $matchingTypes;
        }
        return [];
    }
    public static function toBenevolentUnion(\PHPStan\Type\Type $type): \PHPStan\Type\Type
    {
        if ($type instanceof \PHPStan\Type\BenevolentUnionType) {
            return $type;
        }
        if ($type instanceof \PHPStan\Type\UnionType) {
            return new \PHPStan\Type\BenevolentUnionType($type->getTypes());
        }
        return $type;
    }
    /**
     * @return ($type is UnionType ? UnionType : Type)
     */
    public static function toStrictUnion(\PHPStan\Type\Type $type): \PHPStan\Type\Type
    {
        if ($type instanceof TemplateBenevolentUnionType) {
            return new TemplateUnionType($type->getScope(), $type->getStrategy(), $type->getVariance(), $type->getName(), static::toStrictUnion($type->getBound()), $type->getDefault());
        }
        if ($type instanceof \PHPStan\Type\BenevolentUnionType) {
            return new \PHPStan\Type\UnionType($type->getTypes());
        }
        return $type;
    }
    /**
     * @return Type[]
     */
    public static function flattenTypes(\PHPStan\Type\Type $type): array
    {
        if ($type instanceof \PHPStan\Type\UnionType) {
            $types = [];
            foreach ($type->getTypes() as $innerType) {
                $flattenTypes = self::flattenTypes($innerType);
                foreach ($flattenTypes as $flattenType) {
                    $types[] = $flattenType;
                }
            }
            return $types;
        }
        $constantArrays = $type->getConstantArrays();
        if ($constantArrays !== []) {
            // Estimate the total number of power-set variants before expanding.
            // Each ConstantArrayType with N optional keys produces 2^N variants
            // from getAllArrays(). The cartesian product across multiple constant
            // arrays multiplies these counts. Bail out to avoid O(2^N) allocation
            // when the total would be large.
            $estimatedCount = 1;
            $bail = \false;
            foreach ($constantArrays as $constantArray) {
                $optionalCount = count($constantArray->getOptionalKeys());
                $arrayCount = $optionalCount <= 20 ? 1 << $optionalCount : PHP_INT_MAX;
                if ($arrayCount > 16384 || $estimatedCount > 16384 / max($arrayCount, 1)) {
                    $bail = \true;
                    break;
                }
                $estimatedCount *= $arrayCount;
            }
            if ($bail) {
                return [$type];
            }
            $newTypes = [];
            foreach ($constantArrays as $constantArray) {
                $newTypes[] = $constantArray->getAllArrays();
            }
            $result = [];
            foreach (CombinationsHelper::combinations($newTypes) as $combination) {
                $intersected = $combination[0];
                for ($i = 1, $count = count($combination); $i < $count; $i++) {
                    $intersected = \PHPStan\Type\TypeCombinator::intersect($intersected, $combination[$i]);
                }
                if ($intersected instanceof \PHPStan\Type\NeverType) {
                    continue;
                }
                $result[] = $intersected;
            }
            return $result;
        }
        return [$type];
    }
    public static function findThisType(\PHPStan\Type\Type $type): ?\PHPStan\Type\ThisType
    {
        if ($type instanceof \PHPStan\Type\ThisType) {
            return $type;
        }
        if ($type instanceof \PHPStan\Type\UnionType || $type instanceof \PHPStan\Type\IntersectionType) {
            foreach ($type->getTypes() as $innerType) {
                $thisType = self::findThisType($innerType);
                if ($thisType !== null) {
                    return $thisType;
                }
            }
        }
        return null;
    }
    public static function findCallableType(\PHPStan\Type\Type $type): ?\PHPStan\Type\Type
    {
        if ($type->isCallable()->yes()) {
            return $type;
        }
        if ($type instanceof \PHPStan\Type\UnionType) {
            foreach ($type->getTypes() as $innerType) {
                $callableType = self::findCallableType($innerType);
                if ($callableType !== null) {
                    return $callableType;
                }
            }
        }
        return null;
    }
    /**
     * @return HasPropertyType[]
     */
    public static function getHasPropertyTypes(\PHPStan\Type\Type $type): array
    {
        if ($type instanceof HasPropertyType) {
            return [$type];
        }
        if ($type instanceof \PHPStan\Type\UnionType || $type instanceof \PHPStan\Type\IntersectionType) {
            $hasPropertyTypes = [[]];
            foreach ($type->getTypes() as $innerType) {
                $hasPropertyTypes[] = self::getHasPropertyTypes($innerType);
            }
            return array_merge(...$hasPropertyTypes);
        }
        return [];
    }
    /**
     * @return list<AccessoryType>
     */
    public static function getAccessoryTypes(\PHPStan\Type\Type $type): array
    {
        return self::map(AccessoryType::class, $type, \true, \false);
    }
    public static function containsTemplateType(\PHPStan\Type\Type $type): bool
    {
        $containsTemplateType = \false;
        \PHPStan\Type\TypeTraverser::map($type, static function (\PHPStan\Type\Type $type, callable $traverse) use (&$containsTemplateType): \PHPStan\Type\Type {
            if ($type instanceof TemplateType) {
                $containsTemplateType = \true;
            }
            return $containsTemplateType ? $type : $traverse($type);
        });
        return $containsTemplateType;
    }
    public static function resolveLateResolvableTypes(\PHPStan\Type\Type $type, bool $resolveUnresolvableTypes = \true): \PHPStan\Type\Type
    {
        if (!$type->hasTemplateOrLateResolvableType()) {
            return $type;
        }
        return \PHPStan\Type\TypeTraverser::map($type, new LateResolvableTraverser($resolveUnresolvableTypes));
    }
}
