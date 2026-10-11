<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\StatementResult;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Turbo\ReferencedByTurboExtension;
use function count;
/**
 * @api
 */
#[ReferencedByTurboExtension('methodReturnStatementsNode')]
final class MethodReturnStatementsNode extends NodeAbstract implements \PHPStan\Node\ReturnStatementsNode
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
    public ClassReflection $classReflection;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public PhpMethodFromParserNodeReflection $methodReflection;
    private ClassMethod $classMethod;
    /**
     * @param list<ReturnStatement> $returnStatements
     * @param list<ReturnStatement> $returnStatementsAfterFinally
     * @param list<Yield_|YieldFrom> $yieldStatements
     * @param list<ExecutionEndNode> $executionEnds
     * @param ImpurePoint[] $impurePoints
     */
    public function __construct(
        ClassMethod $method,
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
        ClassReflection $classReflection,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        PhpMethodFromParserNodeReflection $methodReflection
    )
    {
        $this->returnStatements = $returnStatements;
        $this->returnStatementsAfterFinally = $returnStatementsAfterFinally;
        $this->yieldStatements = $yieldStatements;
        $this->statementResult = $statementResult;
        $this->executionEnds = $executionEnds;
        $this->impurePoints = $impurePoints;
        $this->classReflection = $classReflection;
        $this->methodReflection = $methodReflection;
        parent::__construct($method->getAttributes());
        $this->classMethod = $method;
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
        return $this->classMethod->byRef;
    }
    public function hasNativeReturnTypehint(): bool
    {
        return $this->classMethod->returnType !== null;
    }
    public function getMethodName(): string
    {
        return $this->classMethod->name->name;
    }
    public function getYieldStatements(): array
    {
        return $this->yieldStatements;
    }
    public function getClassReflection(): ClassReflection
    {
        return $this->classReflection;
    }
    public function getMethodReflection(): PhpMethodFromParserNodeReflection
    {
        return $this->methodReflection;
    }
    /**
     * @return Stmt[]
     */
    public function getStatements(): array
    {
        $stmts = $this->classMethod->stmts;
        if ($stmts === null) {
            return [];
        }
        return $stmts;
    }
    public function isGenerator(): bool
    {
        return count($this->yieldStatements) > 0;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_MethodReturnStatementsNode';
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
