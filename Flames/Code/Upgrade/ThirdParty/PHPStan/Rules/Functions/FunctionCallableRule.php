<?php

declare (strict_types=1);
namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\FunctionCallableNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
use function strtolower;
/**
 * @implements Rule<FunctionCallableNode>
 */
#[RegisteredRule(0)]
final class FunctionCallableRule implements Rule
{
    private ReflectionProvider $reflectionProvider;
    private RuleLevelHelper $ruleLevelHelper;
    private bool $checkFunctionNameCase;
    private bool $reportMaybes;
    public function __construct(
        ReflectionProvider $reflectionProvider,
        RuleLevelHelper $ruleLevelHelper,
        #[AutowiredParameter]
        bool $checkFunctionNameCase,
        #[AutowiredParameter]
        bool $reportMaybes
    )
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->ruleLevelHelper = $ruleLevelHelper;
        $this->checkFunctionNameCase = $checkFunctionNameCase;
        $this->reportMaybes = $reportMaybes;
    }
    public function getNodeType(): string
    {
        return FunctionCallableNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$scope->getPhpVersion()->supportsFirstClassCallables()->yes()) {
            return [RuleErrorBuilder::message('First-class callables are supported only on PHP 8.1 and later.')->nonIgnorable()->identifier('callable.notSupported')->build()];
        }
        $functionName = $node->name;
        if ($functionName instanceof Node\Name) {
            $functionNameName = $functionName->name;
            if ($this->reflectionProvider->hasFunction($functionName, $scope)) {
                if ($this->checkFunctionNameCase) {
                    $function = $this->reflectionProvider->getFunction($functionName, $scope);
                    /** @var string $calledFunctionName */
                    $calledFunctionName = $this->reflectionProvider->resolveFunctionName($functionName, $scope);
                    if (strtolower($function->getName()) === strtolower($calledFunctionName) && $function->getName() !== $calledFunctionName) {
                        return [RuleErrorBuilder::message(sprintf('Call to function %s() with incorrect case: %s', $function->getName(), $functionNameName))->identifier('function.nameCase')->build()];
                    }
                }
                return [];
            }
            if ($scope->isInFunctionExists($functionNameName)) {
                return [];
            }
            return [RuleErrorBuilder::message(sprintf('Function %s not found.', $functionNameName))->identifier('function.notFound')->build()];
        }
        $typeResult = $this->ruleLevelHelper->findTypeToCheck($scope, NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope($scope, $functionName), 'Creating callable from an unknown class %s.', static fn(Type $type): bool => $type->isCallable()->yes());
        $type = $typeResult->type;
        if ($type instanceof ErrorType) {
            return $typeResult->unknownClassErrors;
        }
        $isCallable = $type->isCallable();
        if ($isCallable->no()) {
            return [RuleErrorBuilder::message(sprintf('Creating callable from %s but it\'s not a callable.', $type->describe(VerbosityLevel::value())))->identifier('callable.nonCallable')->build()];
        }
        if ($this->reportMaybes && $isCallable->maybe()) {
            return [RuleErrorBuilder::message(sprintf('Creating callable from %s but it might not be a callable.', $type->describe(VerbosityLevel::value())))->identifier('callable.nonCallable')->build()];
        }
        return [];
    }
}
