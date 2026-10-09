<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Naming\PhpDoc;

use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\PhpDocAttributeKey;
final class VarTagValueNodeRenamer
{
    public function renameAssignVarTagVariableName(PhpDocInfo $phpDocInfo, string $originalName, string $expectedName): void
    {
        $varTagValueNode = $phpDocInfo->getVarTagValueNode();
        if (!$varTagValueNode instanceof VarTagValueNode) {
            return;
        }
        if ($varTagValueNode->variableName !== '$' . $originalName) {
            return;
        }
        $varTagValueNode->variableName = '$' . $expectedName;
        // invoke node reprint - same as in php-parser
        $varTagValueNode->setAttribute(PhpDocAttributeKey::ORIG_NODE, null);
    }
}
