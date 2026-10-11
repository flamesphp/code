<?php

declare (strict_types=1);
namespace PHPStan\Node;

use Override;
use PhpParser\Node\PropertyHook;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\StatementResult;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Reflection\Php\PhpPropertyReflection;
use PHPStan\Turbo\ReferencedByTurboExtension;
/**
 * @api
 */
#[ReferencedByTurboExtension('propertyHookReturnStatementsNode')]
final class PropertyHookReturnStatementsNode extends NodeAbstract implements \PHPStan\Node\ReturnStatementsNode
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public PropertyHook $hook;
    /**
     * @var list<ReturnStatement>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $returnStatements;
    /**
     * @var list<ReturnStatement>
     */
    private array $returnStatementsAfterFinally;
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
    public PhpMethodFromParserNodeReflection $hookReflection;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public PhpPropertyReflection $propertyReflection;
    /**
     * @param list<ReturnStatement> $returnStatements
     * @param list<ReturnStatement> $returnStatementsAfterFinally
     * @param list<ExecutionEndNode> $executionEnds
     * @param ImpurePoint[] $impurePoints
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        PropertyHook $hook,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $returnStatements,
        array $returnStatementsAfterFinally,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        StatementResult $statementResult,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $executionEnds,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $impurePoints,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        ClassReflection $classReflection,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        PhpMethodFromParserNodeReflection $hookReflection,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        PhpPropertyReflection $propertyReflection
    )
    {
        $this->hook = $hook;
        $this->returnStatements = $returnStatements;
        $this->returnStatementsAfterFinally = $returnStatementsAfterFinally;
        $this->statementResult = $statementResult;
        $this->executionEnds = $executionEnds;
        $this->impurePoints = $impurePoints;
        $this->classReflection = $classReflection;
        $this->hookReflection = $hookReflection;
        $this->propertyReflection = $propertyReflection;
        parent::__construct($hook->getAttributes());
    }
    public function getPropertyHookNode(): PropertyHook
    {
        return $this->hook;
    }
    public function returnsByRef(): bool
    {
        return $this->hook->byRef;
    }
    public function hasNativeReturnTypehint(): bool
    {
        return \false;
    }
    public function getYieldStatements(): array
    {
        return [];
    }
    public function isGenerator(): bool
    {
        return \false;
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
    public function getClassReflection(): ClassReflection
    {
        return $this->classReflection;
    }
    public function getHookReflection(): PhpMethodFromParserNodeReflection
    {
        return $this->hookReflection;
    }
    public function getPropertyReflection(): PhpPropertyReflection
    {
        return $this->propertyReflection;
    }
    #[Override]
    public function getType(): string
    {
        return 'PHPStan_Node_PropertyHookReturnStatementsNode';
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
