<?php

declare (strict_types=1);
namespace PHPStan\Dependency;

use PhpParser\NodeTraverser;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;
#[AutowiredService]
final class ExportedNodeFetcher
{
    private Parser $parser;
    private \PHPStan\Dependency\ExportedNodeVisitor $visitor;
    public function __construct(
        #[AutowiredParameter('@defaultAnalysisParser')]
        Parser $parser,
        \PHPStan\Dependency\ExportedNodeVisitor $visitor
    )
    {
        $this->parser = $parser;
        $this->visitor = $visitor;
    }
    /**
     * @return RootExportedNode[]
     */
    public function fetchNodes(string $fileName): array
    {
        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor($this->visitor);
        try {
            $ast = $this->parser->parseFile($fileName);
        } catch (ParserErrorsException $e) {
            return [];
        }
        $this->visitor->reset($fileName);
        $nodeTraverser->traverse($ast);
        return $this->visitor->currentNodes;
    }
}
