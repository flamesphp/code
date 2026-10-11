<?php

declare (strict_types=1);
namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\ReturnStatementsNode;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use function count;
use function spl_object_id;
use function sprintf;
use function ucfirst;
/**
 * A function returning by reference hands out a reference to the returned expression.
 * Because a finally block runs after the return statement, whatever it does to that
 * expression is what the caller actually receives.
 *
 * @implements Rule<ReturnStatementsNode>
 */
#[RegisteredRule(3)]
final class ReturnTypeAfterFinallyRule implements Rule
{
    private RuleLevelHelper $ruleLevelHelper;
    public function __construct(RuleLevelHelper $ruleLevelHelper)
    {
        $this->ruleLevelHelper = $ruleLevelHelper;
    }
    public function getNodeType(): string
    {
        return ReturnStatementsNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->returnsByRef() || $node->isGenerator()) {
            return [];
        }
        if (count($node->getReturnStatementsAfterFinally()) === 0) {
            return [];
        }
        $described = $this->describeFunction($scope);
        if ($described === null) {
            return [];
        }
        [$description, $returnType] = $described;
        $returnType = TypeUtils::resolveLateResolvableTypes($returnType);
        $scopesAtReturn = [];
        foreach ($node->getReturnStatements() as $returnStatement) {
            $scopesAtReturn[spl_object_id($returnStatement->returnNode)] = $returnStatement->scope;
        }
        // A return statement wrapped in several try-finally blocks is reported once,
        // through the outermost finally block - the last one to run.
        $afterFinally = [];
        foreach ($node->getReturnStatementsAfterFinally() as $returnStatement) {
            $afterFinally[spl_object_id($returnStatement->returnNode)] = $returnStatement;
        }
        $errors = [];
        foreach ($afterFinally as $returnNodeId => $returnStatement) {
            $returnNode = $returnStatement->returnNode;
            $returnExpr = $returnNode->expr;
            if ($returnExpr === null || !isset($scopesAtReturn[$returnNodeId])) {
                continue;
            }
            // void, never and types not matching already at the return statement
            // are reported by the regular return type rules
            $scopeAtReturn = $scopesAtReturn[$returnNodeId];
            if (!$this->ruleLevelHelper->accepts($returnType, $scopeAtReturn->getType($returnExpr), $scopeAtReturn->isDeclareStrictTypes())->result) {
                continue;
            }
            $scopeAfterFinally = $returnStatement->scope;
            $typeAfterFinally = $scopeAfterFinally->getType($returnExpr);
            $accepts = $this->ruleLevelHelper->accepts($returnType, $typeAfterFinally, $scopeAfterFinally->isDeclareStrictTypes());
            if ($accepts->result) {
                continue;
            }
            $verbosityLevel = VerbosityLevel::getRecommendedLevelByType($returnType, $typeAfterFinally);
            $errors[] = RuleErrorBuilder::message(sprintf('%s should return %s but returns %s because the finally block modifies the value returned by reference.', $description, $returnType->describe($verbosityLevel), $typeAfterFinally->describe($verbosityLevel)))->line($returnNode->getStartLine())->identifier('return.byRefFinally')->acceptsReasonsTip($accepts->reasons)->build();
        }
        return $errors;
    }
    /**
     * @return array{string, Type}|null
     */
    private function describeFunction(Scope $scope): ?array
    {
        if ($scope->isInAnonymousFunction()) {
            return ['Anonymous function', $scope->getAnonymousFunctionReturnType()];
        }
        $function = $scope->getFunction();
        if ($function === null) {
            return null;
        }
        if (!$function instanceof PhpMethodFromParserNodeReflection) {
            return [sprintf('Function %s()', $function->getName()), $function->getReturnType()];
        }
        if ($function->hookForProperty !== null) {
            return [sprintf('%s hook for property %s::$%s', ucfirst($function->getPropertyHookName()), $function->declaringClass->getDisplayName(), $function->hookForProperty), $function->getReturnType()];
        }
        return [sprintf('Method %s::%s()', $function->declaringClass->getDisplayName(), $function->getName()), $function->getReturnType()];
    }
}
