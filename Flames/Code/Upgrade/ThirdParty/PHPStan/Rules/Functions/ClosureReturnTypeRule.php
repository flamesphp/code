<?php

declare (strict_types=1);
namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\ClosureReturnStatementsNode;
use PHPStan\Rules\FunctionReturnTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Type\TypeCombinator;
/**
 * @implements Rule<ClosureReturnStatementsNode>
 */
#[RegisteredRule(3)]
final class ClosureReturnTypeRule implements Rule
{
    private FunctionReturnTypeCheck $returnTypeCheck;
    public function __construct(FunctionReturnTypeCheck $returnTypeCheck)
    {
        $this->returnTypeCheck = $returnTypeCheck;
    }
    public function getNodeType(): string
    {
        return ClosureReturnStatementsNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$scope->isInAnonymousFunction()) {
            return [];
        }
        $returnType = $scope->getAnonymousFunctionReturnType();
        $containsNull = TypeCombinator::containsNull($returnType);
        $hasNativeTypehint = $node->closureExpr->returnType !== null;
        $messages = [];
        foreach ($node->returnStatements as $returnStatement) {
            $returnNode = $returnStatement->returnNode;
            $returnExpr = $returnNode->expr;
            if ($returnExpr === null && $containsNull && !$hasNativeTypehint) {
                $returnExpr = new Node\Expr\ConstFetch(new Node\Name\FullyQualified('null'));
            }
            $returnMessages = $this->returnTypeCheck->checkReturnType($returnStatement->scope, $returnType, $returnExpr, $returnNode, 'Anonymous function should return %s but empty return statement found.', 'Anonymous function with return type void returns %s but should not return anything.', 'Anonymous function should return %s but returns %s.', 'Anonymous function should never return but return statement found.', \count($node->yieldStatements) > 0);
            foreach ($returnMessages as $returnMessage) {
                $messages[] = $returnMessage;
            }
        }
        return $messages;
    }
}
