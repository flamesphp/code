<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PostRector\Rector;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\NodeTypeResolver\PhpDoc\NodeAnalyzer\DocBlockNameImporter;
use Flames\Code\Upgrade\PostRector\Guard\AddUseStatementGuard;
final class DocblockNameImportingPostRector extends AbstractPostRector
{
    public function __construct(private readonly DocBlockNameImporter $docBlockNameImporter, private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly DocBlockUpdater $docBlockUpdater, private readonly AddUseStatementGuard $addUseStatementGuard)
    {
    }
    public function enterNode(Node $node): ?\Flames\Code\Upgrade\ThirdParty\PhpParser\Node
    {
        if (!$node instanceof Stmt && !$node instanceof Param) {
            return null;
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        if (!$phpDocInfo instanceof PhpDocInfo) {
            return null;
        }
        $hasDocChanged = $this->docBlockNameImporter->importNames($phpDocInfo->getPhpDocNode(), $node);
        if (!$hasDocChanged) {
            return null;
        }
        $this->addRectorClassWithLine($node);
        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
        return $node;
    }
    /**
     * @param Stmt[] $stmts
     */
    public function shouldTraverse(array $stmts): bool
    {
        return $this->addUseStatementGuard->shouldTraverse($stmts, $this->getFile()->getFilePath());
    }
}
