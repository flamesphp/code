<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\TypeNodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IdentifierTypeNode;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\Type\BracketsAwareUnionTypeNode;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\Type\SpacingAwareArrayTypeNode;
final class MixedArrayTypeNodeAnalyzer
{
    public function hasMixedArrayType(BracketsAwareUnionTypeNode $bracketsAwareUnionTypeNode): bool
    {
        $types = $bracketsAwareUnionTypeNode->types;
        foreach ($types as $type) {
            if ($type instanceof SpacingAwareArrayTypeNode) {
                $typeNode = $type->type;
                if (!$typeNode instanceof IdentifierTypeNode) {
                    continue;
                }
                if ($typeNode->name === 'mixed') {
                    return \true;
                }
            }
        }
        return \false;
    }
}
