<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PostRector\Rector;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Use_;
use Flames\Code\Upgrade\Rules\CodingStyle\Node\NameImporter;
use Flames\Code\Upgrade\Rules\Naming\Naming\UseImportsResolver;
use Flames\Code\Upgrade\PostRector\Guard\AddUseStatementGuard;
final class NameImportingPostRector extends AbstractPostRector
{
    /**
     * @var array<Use_|GroupUse>
     */
    private array $currentUses = [];
    public function __construct(private readonly NameImporter $nameImporter, private readonly UseImportsResolver $useImportsResolver, private readonly AddUseStatementGuard $addUseStatementGuard)
    {
    }
    /**
     * @return Stmt[]
     */
    public function beforeTraverse(array $nodes): array
    {
        $this->currentUses = $this->useImportsResolver->resolve();
        return $nodes;
    }
    public function enterNode(Node $node): ?\PhpParser\Node\Name
    {
        if (!$node instanceof FullyQualified) {
            return null;
        }
        $name = $this->nameImporter->importName($node, $this->getFile(), $this->currentUses);
        if (!$name instanceof Name) {
            return null;
        }
        $this->addRectorClassWithLine($node);
        return $name;
    }
    /**
     * @param Stmt[] $stmts
     */
    public function shouldTraverse(array $stmts): bool
    {
        return $this->addUseStatementGuard->shouldTraverse($stmts, $this->getFile()->getFilePath());
    }
}
