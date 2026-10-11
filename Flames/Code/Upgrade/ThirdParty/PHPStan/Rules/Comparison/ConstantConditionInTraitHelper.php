<?php

declare (strict_types=1);
namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr;
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
final class ConstantConditionInTraitHelper
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
     * @param class-string<Rule<covariant Node>> $ruleName
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\NodeCallbackInvoker&\PHPStan\Analyser\CollectedDataEmitter $scope
     */
    public function emitNoError(string $ruleName, \PHPStan\Analyser\Scope $scope, Expr $expr): void
    {
        if (!$scope->isInTrait()) {
            return;
        }
        $scope->emitCollectedData(\PHPStan\Rules\Comparison\ConstantConditionInTraitCollector::class, [$ruleName, $scope->getTraitReflection()->getName(), \sprintf('%s:%d', $this->exprPrinter->printExpr($expr), $expr->getStartLine()), null]);
    }
    /**
     * @param class-string<Rule<covariant Node>> $ruleName
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\NodeCallbackInvoker&\PHPStan\Analyser\CollectedDataEmitter $scope
     */
    public function emitError(string $ruleName, \PHPStan\Analyser\Scope $scope, Expr $expr, bool $value, RuleError $ruleError): void
    {
        if ($ruleError instanceof FixableNodeRuleError) {
            throw new ShouldNotHappenException('Fixable errors are not supported by ConstantConditionInTraitHelper.');
        }
        if (!$scope->isInTrait()) {
            return;
        }
        $scope->emitCollectedData(\PHPStan\Rules\Comparison\ConstantConditionInTraitCollector::class, [$ruleName, $scope->getTraitReflection()->getName(), \sprintf('%s:%d', $this->exprPrinter->printExpr($expr), $expr->getStartLine()), $value, $this->ruleErrorTransformer->transform($ruleError, $scope, [], $expr)]);
    }
}
