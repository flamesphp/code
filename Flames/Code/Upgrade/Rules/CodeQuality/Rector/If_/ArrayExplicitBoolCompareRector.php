<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodeQuality\Rector\If_;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ElseIf_;
use PhpParser\Node\Stmt\If_;
use Flames\Code\Upgrade\Rules\CodeQuality\NodeAnalyzer\ExplicitBoolConditionResolver;
use Flames\Code\Upgrade\Rules\CodeQuality\ValueObject\ExplicitBoolCondition;
use Flames\Code\Upgrade\NodeTypeResolver\TypeAnalyzer\ArrayTypeAnalyzer;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\CodeQuality\Rector\If_\ArrayExplicitBoolCompareRectorTest
 */
final class ArrayExplicitBoolCompareRector extends AbstractRector
{
    public function __construct(private readonly ArrayTypeAnalyzer $arrayTypeAnalyzer, private readonly ExplicitBoolConditionResolver $explicitBoolConditionResolver)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Make array if conditions more explicit', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeController
{
    public function run(array $items)
    {
        if (!$items) {
            return 'no items';
        }
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeController
{
    public function run(array $items)
    {
        if ($items === []) {
            return 'no items';
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
        return [If_::class, ElseIf_::class, Ternary::class];
    }
    /**
     * @param If_|ElseIf_|Ternary $node
     */
    public function refactor(Node $node): ?Node
    {
        $explicitBoolCondition = $this->explicitBoolConditionResolver->resolve($node);
        if (!$explicitBoolCondition instanceof ExplicitBoolCondition) {
            return null;
        }
        $expr = $explicitBoolCondition->getConditionNode();
        if (!$this->arrayTypeAnalyzer->isArrayType($expr)) {
            return null;
        }
        $binaryOp = $this->resolveArray($explicitBoolCondition->isNegated(), $expr);
        if (!$binaryOp instanceof Expr) {
            return null;
        }
        $node->cond = $binaryOp;
        return $node;
    }
    /**
     * @return \PhpParser\Node\Expr\BinaryOp\Identical|\PhpParser\Node\Expr\BinaryOp\NotIdentical|null
     */
    private function resolveArray(bool $isNegated, Expr $expr)
    {
        if (!$expr instanceof Variable) {
            return null;
        }
        $array = new Array_([]);
        // compare === []
        if ($isNegated) {
            return new Identical($expr, $array);
        }
        return new NotIdentical($expr, $array);
    }
}
