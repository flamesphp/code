<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Naming\ParamRenamer;

use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\ParamTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\BetterPhpDocParser\ValueObject\PhpDocAttributeKey;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\Rules\Naming\ValueObject\ParamRename;
use Flames\Code\Upgrade\Rules\Naming\VariableRenamer;
final readonly class ParamRenamer
{
    public function __construct(private VariableRenamer $variableRenamer, private DocBlockUpdater $docBlockUpdater, private PhpDocInfoFactory $phpDocInfoFactory)
    {
    }
    public function rename(ParamRename $paramRename): void
    {
        // 1. rename param
        $paramRename->getVariable()->name = $paramRename->getExpectedName();
        // 2. rename param in the rest of the method
        $this->variableRenamer->renameVariableInFunctionLike($paramRename->getFunctionLike(), $paramRename->getCurrentName(), $paramRename->getExpectedName());
        // 3. rename @param variable in docblock too
        $this->renameParameterNameInDocBlock($paramRename);
    }
    private function renameParameterNameInDocBlock(ParamRename $paramRename): void
    {
        $functionLike = $paramRename->getFunctionLike();
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($functionLike);
        if (!$phpDocInfo instanceof PhpDocInfo) {
            return;
        }
        $paramTagValueNode = $phpDocInfo->getParamTagValueByName($paramRename->getCurrentName());
        if (!$paramTagValueNode instanceof ParamTagValueNode) {
            return;
        }
        $paramTagValueNode->parameterName = '$' . $paramRename->getExpectedName();
        $paramTagValueNode->setAttribute(PhpDocAttributeKey::ORIG_NODE, null);
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($functionLike);
    }
}
