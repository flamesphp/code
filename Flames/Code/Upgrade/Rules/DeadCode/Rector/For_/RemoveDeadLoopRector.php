<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\For_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Do_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\For_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Foreach_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\While_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor;
use Flames\Code\Upgrade\Rules\DeadCode\SideEffect\SideEffectNodeDetector;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\For_\RemoveDeadLoopRectorTest
 */
final class RemoveDeadLoopRector extends AbstractRector
{
    public function __construct(private readonly SideEffectNodeDetector $sideEffectNodeDetector)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove loop with no body', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run($values)
    {
        for ($i=1; $i<count($values); ++$i) {
        }
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run($values)
    {
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Do_::class, For_::class, Foreach_::class, While_::class];
    }
    /**
     * @param Do_|For_|Foreach_|While_ $node
     */
    public function refactor(Node $node): ?int
    {
        if ($node->stmts !== []) {
            return null;
        }
        if ($node instanceof Do_ || $node instanceof While_) {
            $exprs = [$node->cond];
        } elseif ($node instanceof For_) {
            $exprs = array_merge($node->init, $node->cond, $node->loop);
        } else {
            $exprs = [$node->expr, $node->valueVar];
        }
        foreach ($exprs as $expr) {
            if ($expr instanceof Assign) {
                $expr = $expr->expr;
            }
            if ($this->sideEffectNodeDetector->detect($expr)) {
                return null;
            }
        }
        return NodeVisitor::REMOVE_NODE;
    }
}
