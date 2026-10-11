<?php

declare (strict_types=1);
namespace PHPStan\Analyser\ExprHandler\Virtual;

use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Analyser\VariableFlow;
use PHPStan\Dependency\Dependencies;
use PHPStan\Dependency\DependencyTypes;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\StaticMethodCallableNode;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Type;
use function array_merge;
/**
 * @implements ExprHandler<StaticMethodCallableNode>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(__DIR__ . '/../../../turbo-ext/src/StaticMethodCallableNodeHandler.cpp')]
final class StaticMethodCallableNodeHandler implements ExprHandler
{
    private ExpressionResultFactory $expressionResultFactory;
    private DefaultNarrowingHelper $defaultNarrowingHelper;
    private InitializerExprTypeResolver $initializerExprTypeResolver;
    private ReflectionProvider $reflectionProvider;
    public function __construct(ExpressionResultFactory $expressionResultFactory, DefaultNarrowingHelper $defaultNarrowingHelper, InitializerExprTypeResolver $initializerExprTypeResolver, ReflectionProvider $reflectionProvider)
    {
        $this->expressionResultFactory = $expressionResultFactory;
        $this->defaultNarrowingHelper = $defaultNarrowingHelper;
        $this->initializerExprTypeResolver = $initializerExprTypeResolver;
        $this->reflectionProvider = $reflectionProvider;
    }
    public function supports(Expr $expr): bool
    {
        return $expr instanceof StaticMethodCallableNode;
    }
    public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
    {
        $beforeScope = $scope;
        $throwPoints = [];
        $impurePoints = [];
        $hasYield = \false;
        $isAlwaysTerminating = \false;
        $classResult = null;
        $nameResult = null;
        if ($expr->class instanceof Expr) {
            $classResult = $nodeScopeResolver->processExprNode($stmt, $expr->class, $scope, $storage, $nodeCallback, ExpressionContext::createDeep($context->shouldResolveTemplateArguments()));
            $scope = $classResult->getScope();
            $hasYield = $classResult->hasYield();
            $throwPoints = $classResult->getThrowPoints();
            $impurePoints = $classResult->getImpurePoints();
            $isAlwaysTerminating = $classResult->isAlwaysTerminating();
        }
        if ($expr->name instanceof Expr) {
            $nameResult = $nodeScopeResolver->processExprNode($stmt, $expr->name, $scope, $storage, $nodeCallback, ExpressionContext::createDeep($context->shouldResolveTemplateArguments()));
            $scope = $nameResult->getScope();
            $hasYield = $hasYield || $nameResult->hasYield();
            $throwPoints = array_merge($throwPoints, $nameResult->getThrowPoints());
            $impurePoints = array_merge($impurePoints, $nameResult->getImpurePoints());
            $isAlwaysTerminating = $isAlwaysTerminating || $nameResult->isAlwaysTerminating();
        }
        $result = $this->expressionResultFactory->create($scope, $beforeScope, $expr, $hasYield, $isAlwaysTerminating, $throwPoints, $impurePoints, fn(bool $nativeTypesPromoted): Type => $this->initializerExprTypeResolver->getFirstClassCallableType($expr->originalNode, InitializerExprContext::fromScope($beforeScope), $nativeTypesPromoted), fn(TypeSpecifierContext $context, bool $nativeTypesPromoted) => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context), \false, \null, \null, \null, \null, \null, \null, \null, VariableFlow::sequence($classResult !== null ? $classResult->getVariableFlow() : null, $nameResult !== null ? $nameResult->getVariableFlow() : null));
        return $result->withDependencies(Dependencies::merge($classResult !== null ? $classResult->getDependencies() : null, $nameResult !== null ? $nameResult->getDependencies() : null, $this->getDependencies($beforeScope, $expr, $classResult, $result)));
    }
    /**
     * The class, the class declaring the method and the classes in what calling it returns.
     */
    private function getDependencies(MutatingScope $scope, StaticMethodCallableNode $expr, ?ExpressionResult $classResult, ExpressionResult $result): ?Dependencies
    {
        $types = [];
        $callableType = $result->getType();
        if ($callableType->isCallable()->yes()) {
            foreach ($callableType->getCallableParametersAcceptors($scope) as $variant) {
                $types[] = $variant->getReturnType();
            }
        }
        $classNames = [];
        $name = $expr->name;
        $class = $expr->class;
        $methodReflection = null;
        if ($class instanceof Name) {
            $className = $scope->resolveName($class);
            $classNames[] = $className;
            if ($name instanceof Identifier && $this->reflectionProvider->hasClass($className)) {
                $methodClassReflection = $this->reflectionProvider->getClass($className);
                if ($methodClassReflection->hasMethod($name->name)) {
                    $methodReflection = $methodClassReflection->getMethod($name->name, $scope);
                }
            }
        } elseif ($classResult !== null) {
            $classType = $classResult->getType();
            $types[] = $classType;
            if ($name instanceof Identifier) {
                $methodReflection = $scope->getMethodReflection($classType, $name->name);
            }
        }
        if ($methodReflection !== null) {
            $classNames[] = $methodReflection->getDeclaringClass()->getName();
            $types = [...$types, ...DependencyTypes::ofCalledMethod($methodReflection, \false)];
        }
        return Dependencies::create($scope->getFile(), $types, $classNames);
    }
}
