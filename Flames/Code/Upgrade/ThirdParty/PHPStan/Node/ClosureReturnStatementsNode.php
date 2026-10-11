<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\StatementResult;
use PHPStan\Turbo\ReferencedByTurboExtension;
use function count;
/**
 * @api
 */
#[ReferencedByTurboExtension('closureReturnStatementsNode')]
final class ClosureReturnStatementsNode extends NodeAbstract implements \PHPStan\Node\ReturnStatementsNode
{
    /**
     * @var list<ReturnStatement>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $returnStatements;
    /**
     * @var list<ReturnStatement>
     */
    private array $returnStatementsAfterFinally;
    /**
     * @var list<Yield_|YieldFrom>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $yieldStatements;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public StatementResult $statementResult;
    /**
     * @var list<ExecutionEndNode>
     */
    private array $executionEnds;
    /**
     * @var ImpurePoint[]
     */
    private array $impurePoints;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Node\Expr\Closure $closureExpr;
    /**
     * @param list<ReturnStatement> $returnStatements
     * @param list<ReturnStatement> $returnStatementsAfterFinally
     * @param list<Yield_|YieldFrom> $yieldStatements
     * @param list<ExecutionEndNode> $executionEnds
     * @param ImpurePoint[] $impurePoints
     */
    public function __construct(
        Closure $closureExpr,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $returnStatements,
        array $returnStatementsAfterFinally,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $yieldStatements,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        StatementResult $statementResult,
        array $executionEnds,
        array $impurePoints
    )
    {
        $this->returnStatements = $returnStatements;
        $this->returnStatementsAfterFinally = $returnStatementsAfterFinally;
        $this->yieldStatements = $yieldStatements;
        $this->statementResult = $statementResult;
        $this->executionEnds = $executionEnds;
        $this->impurePoints = $impurePoints;
        parent::__construct($closureExpr->getAttributes());
        $this->closureExpr = $closureExpr;
    }
    public function getClosureExpr(): Closure
    {
        return $this->closureExpr;
    }
    public function hasNativeReturnTypehint(): bool
    {
        return $this->closureExpr->returnType !== null;
    }
    public function getReturnStatements(): array
    {
        return $this->returnStatements;
    }
    public function getReturnStatementsAfterFinally(): array
    {
        return $this->returnStatementsAfterFinally;
    }
    public function getExecutionEnds(): array
    {
        return $this->executionEnds;
    }
    public function getImpurePoints(): array
    {
        return $this->impurePoints;
    }
    public function getYieldStatements(): array
    {
        return $this->yieldStatements;
    }
    public function isGenerator(): bool
    {
        return count($this->yieldStatements) > 0;
    }
    public function getStatementResult(): StatementResult
    {
        return $this->statementResult;
    }
    public function returnsByRef(): bool
    {
        return $this->closureExpr->byRef;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_ClosureReturnStatementsNode';
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
