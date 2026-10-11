<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\StatementResult;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Turbo\ReferencedByTurboExtension;
use function count;
/**
 * @api
 */
#[ReferencedByTurboExtension('functionReturnStatementsNode')]
final class FunctionReturnStatementsNode extends NodeAbstract implements \PHPStan\Node\ReturnStatementsNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public Function_ $function;
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
    private array $yieldStatements;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public StatementResult $statementResult;
    /**
     * @var list<ExecutionEndNode>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $executionEnds;
    /**
     * @var ImpurePoint[]
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $impurePoints;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public PhpFunctionFromParserNodeReflection $functionReflection;
    /**
     * @param list<ReturnStatement> $returnStatements
     * @param list<ReturnStatement> $returnStatementsAfterFinally
     * @param list<Yield_|YieldFrom> $yieldStatements
     * @param list<ExecutionEndNode> $executionEnds
     * @param ImpurePoint[] $impurePoints
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        Function_ $function,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $returnStatements,
        array $returnStatementsAfterFinally,
        array $yieldStatements,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        StatementResult $statementResult,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $executionEnds,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $impurePoints,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        PhpFunctionFromParserNodeReflection $functionReflection
    )
    {
        $this->function = $function;
        $this->returnStatements = $returnStatements;
        $this->returnStatementsAfterFinally = $returnStatementsAfterFinally;
        $this->yieldStatements = $yieldStatements;
        $this->statementResult = $statementResult;
        $this->executionEnds = $executionEnds;
        $this->impurePoints = $impurePoints;
        $this->functionReflection = $functionReflection;
        parent::__construct($function->getAttributes());
    }
    public function getReturnStatements(): array
    {
        return $this->returnStatements;
    }
    public function getReturnStatementsAfterFinally(): array
    {
        return $this->returnStatementsAfterFinally;
    }
    public function getStatementResult(): StatementResult
    {
        return $this->statementResult;
    }
    public function getExecutionEnds(): array
    {
        return $this->executionEnds;
    }
    public function getImpurePoints(): array
    {
        return $this->impurePoints;
    }
    public function returnsByRef(): bool
    {
        return $this->function->byRef;
    }
    public function hasNativeReturnTypehint(): bool
    {
        return $this->function->returnType !== null;
    }
    public function getYieldStatements(): array
    {
        return $this->yieldStatements;
    }
    public function isGenerator(): bool
    {
        return count($this->yieldStatements) > 0;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_FunctionReturnStatementsNode';
    }
    /**
     * @return string[]
     */
    #[Override]
    public function getSubNodeNames(): array
    {
        return [];
    }
    public function getFunctionReflection(): PhpFunctionFromParserNodeReflection
    {
        return $this->functionReflection;
    }
    /**
     * @return Stmt[]
     */
    public function getStatements(): array
    {
        return $this->function->stmts;
    }
}
