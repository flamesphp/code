<?php

declare (strict_types=1);
namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\RuleErrorTransformer;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\ShouldNotHappenException;
use function sprintf;
#[AutowiredService]
final class FunctionCallConstantConditionHelper
{
    private ExprPrinter $exprPrinter;
    private RuleErrorTransformer $ruleErrorTransformer;
    public function __construct(ExprPrinter $exprPrinter, RuleErrorTransformer $ruleErrorTransformer)
    {
        $this->exprPrinter = $exprPrinter;
        $this->ruleErrorTransformer = $ruleErrorTransformer;
    }
    private function exprString(Expr $expr): string
    {
        return sprintf('%s:%d', $this->exprPrinter->printExpr($expr), $expr->getStartLine());
    }
    /**
     * Whether the condition is a function/method/static call that the
     * ImpossibleCheckType* rules might own. For these the constant-condition
     * reporting is deferred to FunctionCallConstantConditionRule, which
     * deduplicates against ImpossibleCheckTypeReportedCollector markers.
     */
    public function isTypeCheckCandidate(Expr $expr): bool
    {
        return ($expr instanceof FuncCall || $expr instanceof MethodCall || $expr instanceof StaticCall) && !$expr->isFirstClassCallable();
    }
    /**
     * @param class-string<Rule<covariant Node>> $ruleName
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\NodeCallbackInvoker&\PHPStan\Analyser\CollectedDataEmitter $scope
     */
    public function emitFunctionCallNoError(string $ruleName, \PHPStan\Analyser\Scope $scope, Expr $expr): void
    {
        $scope->emitCollectedData(\PHPStan\Rules\Comparison\FunctionCallConstantConditionCollector::class, [$ruleName, $scope->isInTrait() ? $scope->getTraitReflection()->getName() : null, \sprintf('%s:%d', $this->exprPrinter->printExpr($expr), $expr->getStartLine()), null]);
    }
    /**
     * @param class-string<Rule<covariant Node>> $ruleName
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\NodeCallbackInvoker&\PHPStan\Analyser\CollectedDataEmitter $scope
     */
    public function emitFunctionCallError(string $ruleName, \PHPStan\Analyser\Scope $scope, Expr $expr, bool $value, RuleError $ruleError): void
    {
        if ($ruleError instanceof FixableNodeRuleError) {
            throw new ShouldNotHappenException('Fixable errors are not supported by FunctionCallConstantConditionHelper.');
        }
        $scope->emitCollectedData(\PHPStan\Rules\Comparison\FunctionCallConstantConditionCollector::class, [$ruleName, $scope->isInTrait() ? $scope->getTraitReflection()->getName() : null, \sprintf('%s:%d', $this->exprPrinter->printExpr($expr), $expr->getStartLine()), $value, $this->ruleErrorTransformer->transform($ruleError, $scope, [], $expr)]);
    }
    /**
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\NodeCallbackInvoker&\PHPStan\Analyser\CollectedDataEmitter $scope
     */
    public function emitImpossibleCheckReported(\PHPStan\Analyser\Scope $scope, Expr $expr): void
    {
        $scope->emitCollectedData(\PHPStan\Rules\Comparison\ImpossibleCheckTypeReportedCollector::class, [\sprintf('%s:%d', $this->exprPrinter->printExpr($expr), $expr->getStartLine())]);
    }
}
