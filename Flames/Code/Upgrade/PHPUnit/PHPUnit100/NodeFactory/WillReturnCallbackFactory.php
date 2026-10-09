<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\PHPUnit100\NodeFactory;

use Flames\Code\Upgrade\ThirdParty\PhpParser\BuilderFactory;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Arg;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\ClosureUse;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayDimFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\BinaryOp\Minus;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\PHPUnit\Enum\ConsecutiveVariable;
use Flames\Code\Upgrade\PHPUnit\NodeFactory\ConsecutiveIfsFactory;
use Flames\Code\Upgrade\PHPUnit\NodeFactory\MatcherInvocationCountMethodCallNodeFactory;
use Flames\Code\Upgrade\PHPUnit\NodeFactory\UsedVariablesResolver;
final readonly class WillReturnCallbackFactory
{
    public function __construct(private BuilderFactory $builderFactory, private UsedVariablesResolver $usedVariablesResolver, private MatcherInvocationCountMethodCallNodeFactory $matcherInvocationCountMethodCallNodeFactory, private ConsecutiveIfsFactory $consecutiveIfsFactory)
    {
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr|null $referenceVariable
     */
    public function createClosure(MethodCall $withConsecutiveMethodCall, ?Stmt $returnStmt, $referenceVariable): Closure
    {
        $matcherVariable = new Variable(ConsecutiveVariable::MATCHER);
        $usedVariables = $this->usedVariablesResolver->resolveUsedVariables($withConsecutiveMethodCall, $returnStmt);
        $closureStmts = $this->createParametersMatch($withConsecutiveMethodCall);
        if ($returnStmt instanceof Stmt) {
            $closureStmts[] = $returnStmt;
        }
        $parametersParam = new Param(new Variable(ConsecutiveVariable::PARAMETERS));
        $parametersParam->variadic = \true;
        return new Closure(['byRef' => $this->isByRef($referenceVariable), 'uses' => $this->createClosureUses($matcherVariable, $usedVariables), 'params' => [$parametersParam], 'stmts' => $closureStmts]);
    }
    /**
     * @return Stmt[]
     */
    public function createParametersMatch(MethodCall $withConsecutiveMethodCall): array
    {
        $parametersVariable = new Variable(ConsecutiveVariable::PARAMETERS);
        $firstArg = $withConsecutiveMethodCall->getArgs()[0] ?? null;
        if ($firstArg instanceof Arg && $firstArg->unpack) {
            $assertSameMethodCall = $this->createAssertSameDimFetch($firstArg, $parametersVariable);
            return [new Expression($assertSameMethodCall)];
        }
        $numberOfInvocationsMethodCall = $this->matcherInvocationCountMethodCallNodeFactory->create();
        return $this->consecutiveIfsFactory->createIfs($withConsecutiveMethodCall, $numberOfInvocationsMethodCall);
    }
    private function createAssertSameDimFetch(Arg $firstArg, Variable $variable): MethodCall
    {
        $matcherCountMethodCall = $this->matcherInvocationCountMethodCallNodeFactory->create();
        $currentValueArrayDimFetch = new ArrayDimFetch($firstArg->value, new Minus($matcherCountMethodCall, new Int_(1)));
        $compareArgs = [new Arg($currentValueArrayDimFetch), new Arg($variable)];
        return $this->builderFactory->methodCall(new Variable('this'), 'assertSame', $compareArgs);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable|null $referenceVariable
     */
    private function isByRef($referenceVariable): bool
    {
        return $referenceVariable instanceof Variable;
    }
    /**
     * @param Variable[] $usedVariables
     * @return ClosureUse[]
     */
    private function createClosureUses(Variable $matcherVariable, array $usedVariables): array
    {
        $uses = [new ClosureUse($matcherVariable)];
        foreach ($usedVariables as $usedVariable) {
            $uses[] = new ClosureUse($usedVariable);
        }
        return $uses;
    }
}
