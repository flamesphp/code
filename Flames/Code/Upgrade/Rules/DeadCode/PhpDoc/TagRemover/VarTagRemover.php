<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\PhpDoc\TagRemover;

use PhpParser\Node;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Property;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\Type\Generic\TemplateObjectWithoutClassType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocManipulator\PhpDocTypeChanger;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\Rules\DeadCode\PhpDoc\DeadVarTagValueNodeAnalyzer;
use Flames\Code\Upgrade\NodeTypeResolver\TypeComparator\TypeComparator;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\DoctrineTypeAnalyzer;
final readonly class VarTagRemover
{
    public function __construct(private DoctrineTypeAnalyzer $doctrineTypeAnalyzer, private PhpDocInfoFactory $phpDocInfoFactory, private DeadVarTagValueNodeAnalyzer $deadVarTagValueNodeAnalyzer, private PhpDocTypeChanger $phpDocTypeChanger, private DocBlockUpdater $docBlockUpdater, private TypeComparator $typeComparator)
    {
    }
    /**
     * @param \PhpParser\Node\Stmt\Property|\PhpParser\Node\Stmt\ClassConst|\PhpParser\Node\Stmt\Expression $node
     */
    public function removeVarTagIfUseless(PhpDocInfo $phpDocInfo, $node): bool
    {
        $varTagValueNode = $phpDocInfo->getVarTagValueNode();
        if (!$varTagValueNode instanceof VarTagValueNode) {
            return \false;
        }
        $isVarTagValueDead = $this->deadVarTagValueNodeAnalyzer->isDead($varTagValueNode, $node);
        if (!$isVarTagValueDead) {
            return \false;
        }
        if ($this->phpDocTypeChanger->isAllowed($varTagValueNode->type)) {
            return \false;
        }
        $phpDocInfo->removeByType(VarTagValueNode::class, $varTagValueNode->variableName);
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
        return \true;
    }
    /**
     * @api generic
     */
    public function removeVarTag(Node $node): bool
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        $varTagValueNode = $phpDocInfo->getVarTagValueNode();
        if (!$varTagValueNode instanceof VarTagValueNode) {
            return \false;
        }
        $phpDocInfo->removeByType(VarTagValueNode::class);
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
        return \true;
    }
    /**
     * @param \PhpParser\Node\Stmt\Expression|\PhpParser\Node\Stmt\Property|\PhpParser\Node\Param $node
     */
    public function removeVarPhpTagValueNodeIfNotComment($node, Type $type): void
    {
        if ($type instanceof TemplateObjectWithoutClassType) {
            return;
        }
        // keep doctrine collection narrow type
        if ($this->doctrineTypeAnalyzer->isDoctrineCollectionWithIterableUnionType($type)) {
            return;
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);
        $varTagValueNode = $phpDocInfo->getVarTagValueNode();
        if (!$varTagValueNode instanceof VarTagValueNode) {
            return;
        }
        // has description? keep it
        if ($varTagValueNode->description !== '') {
            return;
        }
        // keep string[] etc.
        if ($this->phpDocTypeChanger->isAllowed($varTagValueNode->type)) {
            return;
        }
        // keep subtypes like positive-int
        if ($this->shouldKeepSubtypes($type, $phpDocInfo->getVarType())) {
            return;
        }
        $phpDocInfo->removeByType(VarTagValueNode::class);
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
    }
    private function shouldKeepSubtypes(Type $type, Type $varType): bool
    {
        return !$this->typeComparator->areTypesEqual($type, $varType) && $this->typeComparator->isSubtype($varType, $type);
    }
}
