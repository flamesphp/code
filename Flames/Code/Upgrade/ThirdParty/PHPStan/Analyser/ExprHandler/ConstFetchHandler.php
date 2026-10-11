<?php

declare (strict_types=1);
namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ConstantResolver;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Dependency\Dependencies;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use function in_array;
use function strtolower;
/**
 * @implements ExprHandler<ConstFetch>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(__DIR__ . '/../../turbo-ext/src/ConstFetchHandler.cpp')]
final class ConstFetchHandler implements ExprHandler
{
    private ConstantResolver $constantResolver;
    private ExpressionResultFactory $expressionResultFactory;
    private DefaultNarrowingHelper $defaultNarrowingHelper;
    private ReflectionProvider $reflectionProvider;
    public function __construct(ConstantResolver $constantResolver, ExpressionResultFactory $expressionResultFactory, DefaultNarrowingHelper $defaultNarrowingHelper, ReflectionProvider $reflectionProvider)
    {
        $this->constantResolver = $constantResolver;
        $this->expressionResultFactory = $expressionResultFactory;
        $this->defaultNarrowingHelper = $defaultNarrowingHelper;
        $this->reflectionProvider = $reflectionProvider;
    }
    public function supports(Expr $expr): bool
    {
        return $expr instanceof ConstFetch;
    }
    public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
    {
        $nodeScopeResolver->callNodeCallback($nodeCallback, $expr->name, $scope, $storage);
        $dependencies = null;
        if (!in_array(strtolower($expr->name->name), ['true', 'false', 'null'], \true) && $this->reflectionProvider->hasConstant($expr->name, $scope)) {
            $dependencies = Dependencies::create($scope->getFile(), [], [], [$this->reflectionProvider->getConstant($expr->name, $scope)]);
        }
        return $this->expressionResultFactory->create($scope, $scope, $expr, \false, \false, [], [], function (bool $nativeTypesPromoted) use ($expr, $scope): Type {
            $constName = (string) $expr->name;
            $loweredConstName = strtolower($constName);
            if ($loweredConstName === 'true') {
                return new ConstantBooleanType(\true);
            } elseif ($loweredConstName === 'false') {
                return new ConstantBooleanType(\false);
            } elseif ($loweredConstName === 'null') {
                return new NullType();
            }
            $namespacedName = null;
            if (!$expr->name->isFullyQualified() && $scope->getNamespace() !== null) {
                $namespacedName = new FullyQualified([$scope->getNamespace(), $expr->name->name]);
            }
            $globalName = new FullyQualified($expr->name->name);
            foreach ([$namespacedName, $globalName] as $name) {
                if ($name === null) {
                    continue;
                }
                $constFetch = new ConstFetch($name);
                if ($scope->hasExpressionType($constFetch)->yes()) {
                    return $this->constantResolver->resolveConstantType($name->name, $scope->expressionTypes[$scope->getNodeKey($constFetch)]->getType());
                }
            }
            $constantType = $this->constantResolver->resolveConstant($expr->name, $scope);
            if ($constantType !== null) {
                return $constantType;
            }
            return new ErrorType();
        }, fn(TypeSpecifierContext $context, bool $nativeTypesPromoted) => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context), \false, \null, \null, \null, \null, \null, \null, \null, \null, $dependencies);
    }
}
