<?php

declare (strict_types=1);
namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Expr\ForeachValueByRefExpr;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Node\VariableAssignNode;
use PHPStan\Rules\MultipleNodeTypesRule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
/**
 * @implements MultipleNodeTypesRule<NodeAbstract>
 */
final class AssignToByRefExprFromForeachRule implements MultipleNodeTypesRule
{
    private ExprPrinter $printer;
    public function __construct(ExprPrinter $printer)
    {
        $this->printer = $printer;
    }
    public function getNodeTypes(): array
    {
        return [VariableAssignNode::class, PropertyAssignNode::class];
    }
    public function getNodeType(): string
    {
        return NodeAbstract::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node instanceof VariableAssignNode) {
            $expr = $node->variable;
        } elseif ($node instanceof PropertyAssignNode) {
            $expr = $node->propertyFetch;
        } else {
            return [];
        }
        if ($scope->hasExpressionType(new ForeachValueByRefExpr($expr))->no()) {
            return [];
        }
        return [RuleErrorBuilder::message(sprintf('Assign to %s overwrites the last element from array.', $this->printer->printExpr($expr)))->identifier('assign.byRefForeachExpr')->tip('Unset it right after foreach to avoid this problem.')->build()];
    }
}
