<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\Rector\Foreach_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Foreach_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\NodeFinder;
use Flames\Code\Upgrade\ThirdParty\PHPStan\Ast\PhpDoc\VarTagValueNode;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\Rules\DeadCode\NodeAnalyzer\ExprUsedInNodeAnalyzer;
use Flames\Code\Upgrade\NodeManipulator\StmtsManipulator;
use Flames\Code\Upgrade\PhpParser\Enum\NodeGroup;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\DeadCode\Rector\Foreach_\RemoveUnusedForeachKeyRectorTest
 */
final class RemoveUnusedForeachKeyRector extends AbstractRector
{
    public function __construct(private readonly NodeFinder $nodeFinder, private readonly StmtsManipulator $stmtsManipulator, private readonly PhpDocInfoFactory $phpDocInfoFactory, private readonly DocBlockUpdater $docBlockUpdater, private readonly ExprUsedInNodeAnalyzer $exprUsedInNodeAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove unused key in foreach', [new CodeSample(<<<'CODE_SAMPLE'
$items = [];
foreach ($items as $key => $value) {
    $result = $value;
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$items = [];
foreach ($items as $value) {
    $result = $value;
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
        if ($node->stmts === null) {
            return null;
        }
        $hasChanged = \false;
        foreach ($node->stmts as $key => $stmt) {
            if (!$stmt instanceof Foreach_) {
                continue;
            }
            if (!$stmt->keyVar instanceof Variable) {
                continue;
            }
            $keyVar = $stmt->keyVar;
            $isNodeUsed = (bool) $this->nodeFinder->findFirst($stmt->stmts, fn(Node $node): bool => $this->exprUsedInNodeAnalyzer->isUsed($node, $keyVar));
            if ($isNodeUsed) {
                continue;
            }
            $keyVarName = (string) $this->getName($keyVar);
            if ($this->stmtsManipulator->isVariableUsedInNextStmt($node, $key + 1, $keyVarName)) {
                continue;
            }
            $stmt->keyVar = null;
            $hasChanged = \true;
            $phpDocInfo = $this->phpDocInfoFactory->createFromNode($stmt);
            if (!$phpDocInfo instanceof PhpDocInfo) {
                continue;
            }
            $varTagValues = $phpDocInfo->getPhpDocNode()->getVarTagValues();
            foreach ($varTagValues as $varTagValue) {
                $variableName = $varTagValue->variableName;
                if ($varTagValue->variableName === '$' . $keyVarName) {
                    $phpDocInfo->removeByType(VarTagValueNode::class, $variableName);
                    $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($stmt);
                }
            }
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
}
