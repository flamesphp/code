<?php

declare (strict_types=1);
namespace PHPStan\Rules\Playground;

use PhpParser\Node;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\ReturnStatementsNode;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
#[AutowiredService]
final class NeverRuleHelper
{
    /**
     * @return list<Node>|false
     */
    public function shouldReturnNever(ReturnStatementsNode $node, Type $returnType)
    {
        if ($returnType instanceof NeverType && $returnType->isExplicit()) {
            return \false;
        }
        if ($node->isGenerator()) {
            return \false;
        }
        $other = [];
        foreach ($node->getExecutionEnds() as $executionEnd) {
            if ($executionEnd->statementResult->isAlwaysTerminating()) {
                $executionEndNode = $executionEnd->node;
                if (!$executionEndNode instanceof Node\Stmt\Expression) {
                    $other[] = $executionEnd->node;
                    continue;
                }
                if ($executionEndNode->expr instanceof Node\Expr\Throw_) {
                    continue;
                }
                $other[] = $executionEnd->node;
                continue;
            }
            return \false;
        }
        return $other;
    }
}
