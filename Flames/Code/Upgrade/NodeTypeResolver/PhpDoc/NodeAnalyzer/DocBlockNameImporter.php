<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeTypeResolver\PhpDoc\NodeAnalyzer;

use PhpParser\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use Flames\Code\Upgrade\NodeTypeResolver\PhpDocNodeVisitor\NameImportingPhpDocNodeVisitor;
use Flames\Code\Upgrade\PhpDocParser\PhpDocParser\PhpDocNodeTraverser;
final readonly class DocBlockNameImporter
{
    public function __construct(private NameImportingPhpDocNodeVisitor $nameImportingPhpDocNodeVisitor)
    {
    }
    public function importNames(PhpDocNode $phpDocNode, Node $node): bool
    {
        if ($phpDocNode->children === []) {
            return \false;
        }
        $this->nameImportingPhpDocNodeVisitor->setCurrentNode($node);
        $phpDocNodeTraverser = new PhpDocNodeTraverser();
        $phpDocNodeTraverser->addPhpDocNodeVisitor($this->nameImportingPhpDocNodeVisitor);
        $phpDocNodeTraverser->traverse($phpDocNode);
        return $this->nameImportingPhpDocNodeVisitor->hasChanged();
    }
}
