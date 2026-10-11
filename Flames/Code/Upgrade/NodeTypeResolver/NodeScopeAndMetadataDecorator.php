<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\NodeTypeResolver;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeTraverser;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor\CloningVisitor;
use Flames\Code\Upgrade\NodeTypeResolver\PHPStan\Scope\PHPStanNodeScopeResolver;
final readonly class NodeScopeAndMetadataDecorator
{
    private NodeTraverser $nodeTraverser;
    public function __construct(CloningVisitor $cloningVisitor, private PHPStanNodeScopeResolver $phpStanNodeScopeResolver)
    {
        // needed for format preserving printing
        $this->nodeTraverser = new NodeTraverser($cloningVisitor);
    }
    /**
     * @param Stmt[] $stmts
     * @return Stmt[]
     */
    public function decorateNodesFromFile(string $filePath, array $stmts): array
    {
        $stmts = $this->phpStanNodeScopeResolver->processNodes($stmts, $filePath);
        return $this->nodeTraverser->traverse($stmts);
    }
}
