<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\EarlyReturn\Rector\StmtsAwareInterface;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Else_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\If_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\VarTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\NodeAnalyzer\VariableAnalyzer;
use Flames\Code\Upgrade\PhpParser\Enum\NodeGroup;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\EarlyReturn\Rector\StmtsAwareInterface\ReturnEarlyIfVariableRectorTest
 */
final class ReturnEarlyIfVariableRector extends AbstractRector
{
    public function __construct(private readonly VariableAnalyzer $variableAnalyzer, private readonly PhpDocInfoFactory $phpDocInfoFactory)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace if conditioned variable override with direct return', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function run($value)
    {
        if ($value === 50) {
            $value = 100;
        }

        return $value;
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function run($value)
    {
        if ($value === 50) {
            return 100;
        }

        return $value;
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
        return NodeGroup::STMTS_AWARE;
    }
    /**
     * @param StmtsAware $node
     */
    public function refactor(Node $node): ?Node
    {
        $stmts = (array) $node->stmts;
        foreach ($stmts as $key => $stmt) {
            $returnVariable = $this->matchNextStmtReturnVariable($node, $key);
            if (!$returnVariable instanceof Variable) {
                continue;
            }
            if ($stmt instanceof If_ && !$stmt->else instanceof Else_ && $stmt->elseifs === []) {
                // is single condition if
                $if = $stmt;
                if (count($if->stmts) !== 1) {
                    continue;
                }
                $onlyIfStmt = $if->stmts[0];
                $assignedExpr = $this->matchOnlyIfStmtReturnExpr($onlyIfStmt, $returnVariable);
                if (!$assignedExpr instanceof Expr) {
                    continue;
                }
                $if->stmts[0] = new Return_($assignedExpr);
                $this->mirrorComments($if->stmts[0], $onlyIfStmt);
                return $node;
            }
        }
        return null;
    }
    private function matchOnlyIfStmtReturnExpr(Stmt $onlyIfStmt, Variable $returnVariable): ?\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr
    {
        if (!$onlyIfStmt instanceof Expression) {
            return null;
        }
        if (!$onlyIfStmt->expr instanceof Assign) {
            return null;
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($onlyIfStmt);
        if ($phpDocInfo->getVarTagValueNode() instanceof VarTagValueNode) {
            return null;
        }
        $assign = $onlyIfStmt->expr;
        // assign to same variable that is returned
        if (!$assign->var instanceof Variable) {
            return null;
        }
        if ($this->variableAnalyzer->isStaticOrGlobal($assign->var)) {
            return null;
        }
        if ($this->variableAnalyzer->isUsedByReference($assign->var)) {
            return null;
        }
        if (!$this->nodeComparator->areNodesEqual($assign->var, $returnVariable)) {
            return null;
        }
        // return directly
        return $assign->expr;
    }
    /**
     * @param StmtsAware $stmtsAware
     */
    private function matchNextStmtReturnVariable(Node $stmtsAware, int $key): ?\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable
    {
        $nextStmt = $stmtsAware->stmts[$key + 1] ?? null;
        // last item → stop
        if (!$nextStmt instanceof Stmt) {
            return null;
        }
        if (!$nextStmt instanceof Return_) {
            return null;
        }
        // next return must be variable
        if (!$nextStmt->expr instanceof Variable) {
            return null;
        }
        return $nextStmt->expr;
    }
}
