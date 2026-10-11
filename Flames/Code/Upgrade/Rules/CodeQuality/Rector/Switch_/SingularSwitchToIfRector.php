<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\Switch_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Identical;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\FunctionLike;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Break_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Continue_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Switch_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeVisitor;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Rules\Renaming\NodeManipulator\SwitchManipulator;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\Switch_\SingularSwitchToIfRectorTest
 */
final class SingularSwitchToIfRector extends AbstractRector
{
    public function __construct(private readonly SwitchManipulator $switchManipulator)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change `switch` with only 1 check to `if`', [new CodeSample(<<<'CODE_SAMPLE'
class SomeObject
{
    public function run($value)
    {
        $result = 1;
        switch ($value) {
            case 100:
            $result = 1000;
        }

        return $result;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeObject
{
    public function run($value)
    {
        $result = 1;
        if ($value === 100) {
            $result = 1000;
        }

        return $result;
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
        return [Switch_::class];
    }
    /**
     * @param Switch_ $node
     * @return Node\Stmt[]|If_|null
     */
    public function refactor(Node $node)
    {
        if (count($node->cases) !== 1) {
            return null;
        }
        $onlyCase = $node->cases[0];
        // nested break/continue would lose the switch to target and cause a fatal error
        if ($this->hasNestedBreakOrContinue($onlyCase->stmts)) {
            return null;
        }
        // only default → basically unwrap
        if (!$onlyCase->cond instanceof Expr) {
            // remove default clause because it cause syntax error
            return array_filter($onlyCase->stmts, static fn(Stmt $stmt): bool => !$stmt instanceof Break_);
        }
        $if = new If_(new Identical($node->cond, $onlyCase->cond));
        $if->stmts = $this->switchManipulator->removeBreakNodes($onlyCase->stmts);
        return $if;
    }
    /**
     * @param Stmt[] $stmts
     */
    private function hasNestedBreakOrContinue(array $stmts): bool
    {
        $hasNested = \false;
        foreach ($stmts as $stmt) {
            // top level break is removed by SwitchManipulator
            if ($stmt instanceof Break_) {
                continue;
            }
            $this->traverseNodesWithCallable($stmt, static function (Node $subNode) use (&$hasNested): ?int {
                if ($subNode instanceof Class_ || $subNode instanceof FunctionLike) {
                    return NodeVisitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
                }
                if ($subNode instanceof Break_ || $subNode instanceof Continue_) {
                    $hasNested = \true;
                    return NodeVisitor::STOP_TRAVERSAL;
                }
                return null;
            });
        }
        return $hasNested;
    }
}
