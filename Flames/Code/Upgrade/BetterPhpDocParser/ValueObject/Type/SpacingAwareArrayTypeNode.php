<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\Type;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\ArrayTypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\CallableTypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\GenericTypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\UnionTypeNode;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\TypeMapper\ArrayTypeMapper;
use Stringable;
final class SpacingAwareArrayTypeNode extends ArrayTypeNode
{
    public function __toString(): string
    {
        if ($this->type instanceof CallableTypeNode) {
            return sprintf('(%s)[]', (string) $this->type);
        }
        $typeAsString = (string) $this->type;
        if ($this->isGenericArrayCandidate($this->type)) {
            return sprintf('array<%s>', $typeAsString);
        }
        if ($this->type instanceof ArrayTypeNode) {
            return $this->printArrayType($this->type);
        }
        if ($this->type instanceof \Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\Type\BracketsAwareUnionTypeNode) {
            return $this->printUnionType($this->type);
        }
        return $typeAsString . '[]';
    }
    private function isGenericArrayCandidate(TypeNode $typeNode): bool
    {
        $hasGenericTypeParent = (bool) $this->getAttribute(ArrayTypeMapper::HAS_GENERIC_TYPE_PARENT);
        if (!$hasGenericTypeParent) {
            return \false;
        }
        return $typeNode instanceof UnionTypeNode || $typeNode instanceof ArrayTypeNode;
    }
    private function printArrayType(ArrayTypeNode $arrayTypeNode): string
    {
        $typeAsString = (string) $arrayTypeNode;
        $singleTypesAsString = explode('|', $typeAsString);
        foreach ($singleTypesAsString as $key => $singleTypeAsString) {
            $singleTypesAsString[$key] = $singleTypeAsString . '[]';
        }
        return implode('|', $singleTypesAsString);
    }
    private function printUnionType(\Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\Type\BracketsAwareUnionTypeNode $bracketsAwareUnionTypeNode): string
    {
        if ($bracketsAwareUnionTypeNode->isWrappedInBrackets()) {
            return $bracketsAwareUnionTypeNode . '[]';
        }
        // If all types in the union are GenericTypeNode, use array<union> syntax
        $allGeneric = \true;
        $firstGenericTypeName = null;
        foreach ($bracketsAwareUnionTypeNode->types as $unionedType) {
            if (!$unionedType instanceof GenericTypeNode) {
                $allGeneric = \false;
                break;
            }
            // ensure only check on base level
            // avoid mix usage without [] added
            if (count($unionedType->genericTypes) !== 1) {
                $allGeneric = \false;
                break;
            }
            // ensure all generic types has the same base type
            $currentTypeName = $unionedType->type->name;
            if ($firstGenericTypeName === null) {
                $firstGenericTypeName = $currentTypeName;
            } elseif ($firstGenericTypeName !== $currentTypeName) {
                // Different generic base types (e.g., class-string vs array)
                $allGeneric = \false;
                break;
            }
        }
        if ($allGeneric) {
            return sprintf('array<int, %s>', (string) $bracketsAwareUnionTypeNode);
        }
        $unionedTypes = [];
        foreach ($bracketsAwareUnionTypeNode->types as $unionedType) {
            $unionedTypes[] = $unionedType . '[]';
        }
        return implode('|', $unionedTypes);
    }
}
