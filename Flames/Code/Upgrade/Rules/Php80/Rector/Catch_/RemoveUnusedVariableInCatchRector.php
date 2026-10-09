<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\Rector\Catch_;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Finally_;
use PhpParser\Node\Stmt\TryCatch;
use Flames\Code\Upgrade\Rules\DeadCode\NodeAnalyzer\ExprUsedInNodeAnalyzer;
use Flames\Code\Upgrade\NodeManipulator\StmtsManipulator;
use Flames\Code\Upgrade\PhpParser\Enum\NodeGroup;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ValueObject\PhpVersionFeature;
use Flames\Code\Upgrade\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRectorTest
 */
final class RemoveUnusedVariableInCatchRector extends AbstractRector implements MinPhpVersionInterface
{
    public function __construct(private readonly StmtsManipulator $stmtsManipulator, private readonly BetterNodeFinder $betterNodeFinder, private readonly ExprUsedInNodeAnalyzer $exprUsedInNodeAnalyzer)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove unused variable in `catch()`', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function run()
    {
        try {
        } catch (Throwable $notUsedThrowable) {
        }
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function run()
    {
        try {
        } catch (Throwable) {
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
            if (!$stmt instanceof TryCatch) {
                continue;
            }
            foreach ($stmt->catches as $catch) {
                $caughtVar = $catch->var;
                if (!$caughtVar instanceof Variable) {
                    continue;
                }
                /** @var string $variableName */
                $variableName = $this->getName($caughtVar);
                $isFoundInCatchStmts = (bool) $this->betterNodeFinder->findFirst(array_merge($catch->stmts, $stmt->finally instanceof Finally_ ? $stmt->finally->stmts : []), fn(Node $subNode): bool => $this->exprUsedInNodeAnalyzer->isUsed($subNode, $caughtVar));
                if ($isFoundInCatchStmts) {
                    continue;
                }
                if ($this->stmtsManipulator->isVariableUsedInNextStmt($node, $key + 1, $variableName)) {
                    continue;
                }
                $catch->var = null;
                $hasChanged = \true;
            }
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    public function provideMinPhpVersion(): int
    {
        return PhpVersionFeature::NON_CAPTURING_CATCH;
    }
}
