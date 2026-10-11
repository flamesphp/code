<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\TagNodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ParamTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ReturnTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\VarTagValueNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\IdentifierTypeNode;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\Type\TypeNode;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\Type\SpacingAwareArrayTypeNode;
final class UsefulArrayTagNodeAnalyzer
{
    /**
     * @param null|\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ReturnTagValueNode|\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ParamTagValueNode|\Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\VarTagValueNode $tagValueNode
     */
    public function isUsefulArrayTag($tagValueNode): bool
    {
        if (!$tagValueNode instanceof ReturnTagValueNode && !$tagValueNode instanceof ParamTagValueNode && !$tagValueNode instanceof VarTagValueNode) {
            return \false;
        }
        $type = $tagValueNode->type;
        if (!$type instanceof IdentifierTypeNode) {
            return !$this->isMixedArray($type);
        }
        return !in_array($type->name, ['array', 'mixed', 'iterable'], \true);
    }
    private function isMixedArray(TypeNode $typeNode): bool
    {
        return $typeNode instanceof SpacingAwareArrayTypeNode && $typeNode->type instanceof IdentifierTypeNode && $typeNode->type->name === 'mixed';
    }
}
