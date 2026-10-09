<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\FunctionLike;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Else_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\FunctionLike\RemoveDeadReturnRectorTest
 */
final class RemoveDeadReturnRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove last return in the functions, since does not do anything', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $shallWeDoThis = true;

        if ($shallWeDoThis) {
            return;
        }

        return;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $shallWeDoThis = true;

        if ($shallWeDoThis) {
            return;
        }
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
        return [ClassMethod::class, Function_::class, Closure::class];
    }
    /**
     * @param ClassMethod|Function_|Closure $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->stmts === [] || $node->stmts === null) {
            return null;
        }
        $lastStmtKey = array_key_last($node->stmts);
        $lastStmt = $node->stmts[$lastStmtKey];
        if ($lastStmt instanceof If_) {
            if (!$this->isBareIfWithOnlyStmtEmptyReturn($lastStmt)) {
                return null;
            }
            $lastStmt->stmts = [];
            return $node;
        }
        if (!$lastStmt instanceof Return_) {
            return null;
        }
        if ($lastStmt->expr instanceof Expr) {
            return null;
        }
        unset($node->stmts[$lastStmtKey]);
        return $node;
    }
    private function isBareIfWithOnlyStmtEmptyReturn(If_ $if): bool
    {
        if ($if->else instanceof Else_) {
            return \false;
        }
        if ($if->elseifs !== []) {
            return \false;
        }
        if (count($if->stmts) !== 1) {
            return \false;
        }
        $onlyStmt = $if->stmts[0];
        if (!$onlyStmt instanceof Return_) {
            return \false;
        }
        return !$onlyStmt->expr instanceof Expr;
    }
}
