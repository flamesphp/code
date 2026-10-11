<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeTypeResolver\PhpDoc\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\NodeTypeResolver\PhpDocNodeVisitor\ClassRenamePhpDocNodeVisitor;
use Flames\Code\Upgrade\NodeTypeResolver\ValueObject\OldToNewType;
use Flames\Code\Upgrade\PhpDocParser\PhpDocParser\PhpDocNodeTraverser;
final readonly class DocBlockClassRenamer
{
    public function __construct(private ClassRenamePhpDocNodeVisitor $classRenamePhpDocNodeVisitor)
    {
    }
    /**
     * @param OldToNewType[] $oldToNewTypes
     */
    public function renamePhpDocType(PhpDocInfo $phpDocInfo, array $oldToNewTypes, Node $currentPhpNode): bool
    {
        if ($oldToNewTypes === []) {
            return \false;
        }
        $phpDocNodeTraverser = new PhpDocNodeTraverser();
        $phpDocNodeTraverser->addPhpDocNodeVisitor($this->classRenamePhpDocNodeVisitor);
        $this->classRenamePhpDocNodeVisitor->setCurrentPhpNode($currentPhpNode);
        $this->classRenamePhpDocNodeVisitor->setOldToNewTypes($oldToNewTypes);
        $phpDocNodeTraverser->traverse($phpDocInfo->getPhpDocNode());
        return $this->classRenamePhpDocNodeVisitor->hasChanged();
    }
}
