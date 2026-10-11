<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\StatementResult;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('executionEndNode')]
final class ExecutionEndNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Node\Stmt $node;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public StatementResult $statementResult;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $hasNativeReturnTypehint;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public ?ExpressionResult $exprResult;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Node\Stmt $node,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        StatementResult $statementResult,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $hasNativeReturnTypehint,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ?ExpressionResult $exprResult = null
    )
    {
        $this->node = $node;
        $this->statementResult = $statementResult;
        $this->hasNativeReturnTypehint = $hasNativeReturnTypehint;
        $this->exprResult = $exprResult;
        parent::__construct($node->getAttributes());
    }
    public function getNode(): Node\Stmt
    {
        return $this->node;
    }
    /**
     * The result of the expression the ending statement evaluated. Null when the
     * statement is not an expression statement, for the synthetic statement
     * wrapping a closure with an empty body, and when the expression was walked
     * into a storage the end node is not built in.
     *
     * @internal
     */
    public function getExprResult(): ?ExpressionResult
    {
        return $this->exprResult;
    }
    public function getStatementResult(): StatementResult
    {
        return $this->statementResult;
    }
    public function hasNativeReturnTypehint(): bool
    {
        return $this->hasNativeReturnTypehint;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_ExecutionEndNode';
    }
    /**
     * @return string[]
     */
    #[Override]
    public function getSubNodeNames(): array
    {
        return [];
    }
}
