<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Naming\Rector\Foreach_;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Foreach_;
use Flames\Code\Upgrade\Rules\Naming\ExpectedNameResolver\InflectorSingularResolver;
use Flames\Code\Upgrade\NodeAnalyzer\PropertyFetchAnalyzer;
use Flames\Code\Upgrade\NodeManipulator\StmtsManipulator;
use Flames\Code\Upgrade\PhpParser\Enum\NodeGroup;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchExprVariableRectorTest
 */
final class RenameForeachValueVariableToMatchExprVariableRector extends AbstractRector
{
    public function __construct(private readonly InflectorSingularResolver $inflectorSingularResolver, private readonly PropertyFetchAnalyzer $propertyFetchAnalyzer, private readonly StmtsManipulator $stmtsManipulator, private readonly BetterNodeFinder $betterNodeFinder)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Rename value variable name in foreach loop to match expression variable', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $array = [];
        foreach ($variables as $property) {
            $array[] = $property;
        }
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run()
    {
        $array = [];
        foreach ($variables as $variable) {
            $array[] = $variable;
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
            if (!$stmt instanceof Foreach_) {
                continue;
            }
            $isPropertyFetch = $this->propertyFetchAnalyzer->isLocalPropertyFetch($stmt->expr);
            if (!$stmt->expr instanceof Variable && !$isPropertyFetch) {
                continue;
            }
            $exprName = $this->getName($stmt->expr);
            if ($exprName === null) {
                continue;
            }
            if ($stmt->keyVar instanceof Node) {
                continue;
            }
            $valueVarName = $this->getName($stmt->valueVar);
            if ($valueVarName === null) {
                continue;
            }
            $singularValueVarName = $this->inflectorSingularResolver->resolve($exprName);
            if ($singularValueVarName === $exprName) {
                continue;
            }
            if ($singularValueVarName === $valueVarName) {
                continue;
            }
            $alreadyUsedVariable = $this->betterNodeFinder->findVariableOfName($stmt->stmts, $singularValueVarName);
            if ($alreadyUsedVariable instanceof Variable) {
                continue;
            }
            if ($this->stmtsManipulator->isVariableUsedInNextStmt($node, $key + 1, $singularValueVarName)) {
                continue;
            }
            if ($this->stmtsManipulator->isVariableUsedInNextStmt($node, $key + 1, $valueVarName)) {
                continue;
            }
            $scope = ScopeFetcher::fetch($stmt);
            if ($scope->hasVariableType($singularValueVarName)->yes()) {
                continue;
            }
            $this->processRename($stmt, $valueVarName, $singularValueVarName);
            $hasChanged = \true;
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
    private function processRename(Foreach_ $foreach, string $valueVarName, string $singularValueVarName): void
    {
        $foreach->valueVar = new Variable($singularValueVarName);
        $this->traverseNodesWithCallable($foreach->stmts, function (Node $node) use ($singularValueVarName, $valueVarName): ?Variable {
            if (!$node instanceof Variable) {
                return null;
            }
            if (!$this->isName($node, $valueVarName)) {
                return null;
            }
            return new Variable($singularValueVarName);
        });
    }
}
