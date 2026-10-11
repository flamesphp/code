<?php

declare (strict_types=1);
namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Turbo\ReferencedByTurboExtension;
#[ReferencedByTurboExtension('lineAttributesVisitor')]
final class LineAttributesVisitor extends NodeVisitorAbstract
{
    private ?int $startLine;
    private ?int $endLine;
    public function __construct(?int $startLine, ?int $endLine)
    {
        $this->startLine = $startLine;
        $this->endLine = $endLine;
    }
    #[Override]
    public function enterNode(Node $node): Node
    {
        if ($node->getStartLine() === -1) {
            $node->setAttribute('startLine', $this->startLine);
        }
        if ($node->getEndLine() === -1) {
            $node->setAttribute('endLine', $this->endLine);
        }
        return $node;
    }
}
