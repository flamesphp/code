<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\Block;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Block;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\Block\ReplaceBlockToItsStmtsRectorTest
 * @see https://3v4l.org/ZUfEV
 */
final class ReplaceBlockToItsStmtsRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace Block Stmt with its stmts', [new CodeSample(<<<'CODE_SAMPLE'
{
    echo "statement 1";
    echo PHP_EOL;
    echo "statement 2";
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
echo "statement 1";
echo PHP_EOL;
echo "statement 2";
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Block::class];
    }
    /**
     * @param Block $node
     * @return int|Stmt[]
     */
    public function refactor(Node $node)
    {
        if ($node->stmts === []) {
            return NodeVisitor::REMOVE_NODE;
        }
        return $node->stmts;
    }
}
