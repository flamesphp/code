<?php

declare (strict_types=1);
namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\InternalThrowPoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Analyser\VariableFlow;
use PHPStan\Dependency\Dependencies;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\IncludedFilePathResolver;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use function array_merge;
use function in_array;
use function is_file;
/**
 * @implements ExprHandler<Include_>
 */
#[AutowiredService]
#[ShadowedByTurboExtension(__DIR__ . '/../../turbo-ext/src/IncludeHandler.cpp')]
final class IncludeHandler implements ExprHandler
{
    private ExpressionResultFactory $expressionResultFactory;
    private DefaultNarrowingHelper $defaultNarrowingHelper;
    private IncludedFilePathResolver $includedFilePathResolver;
    public function __construct(ExpressionResultFactory $expressionResultFactory, DefaultNarrowingHelper $defaultNarrowingHelper, IncludedFilePathResolver $includedFilePathResolver)
    {
        $this->expressionResultFactory = $expressionResultFactory;
        $this->defaultNarrowingHelper = $defaultNarrowingHelper;
        $this->includedFilePathResolver = $includedFilePathResolver;
    }
    public function supports(Expr $expr): bool
    {
        return $expr instanceof Include_;
    }
    public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
    {
        $beforeScope = $scope;
        $exprResult = $nodeScopeResolver->processExprNode($stmt, $expr->expr, $scope, $storage, $nodeCallback, $context->enterDeep());
        $identifier = in_array($expr->type, [Include_::TYPE_INCLUDE, Include_::TYPE_INCLUDE_ONCE], \true) ? 'include' : 'require';
        // the included file may read any variable
        $scope = $exprResult->getScope()->afterExtractCall()->invalidateVolatileExpressions();
        $throwPoint = InternalThrowPoint::createImplicit($scope, $expr);
        // an included file is a dependency with no symbol to reflect: nothing in it has to be
        // declared for the analysis to change when it is deleted
        $includedFiles = [];
        foreach ($exprResult->getType()->getConstantStrings() as $constantString) {
            foreach ($this->includedFilePathResolver->resolve($constantString->getValue(), $beforeScope) as $candidatePath) {
                if (!is_file($candidatePath)) {
                    continue;
                }
                $includedFiles[] = $candidatePath;
            }
        }
        return $this->expressionResultFactory->create($scope, $beforeScope, $expr, $exprResult->hasYield(), $exprResult->isAlwaysTerminating(), array_merge($exprResult->getThrowPoints(), [$throwPoint]), array_merge($exprResult->getImpurePoints(), [new ImpurePoint($scope, $expr, $identifier, $identifier, \true)]), static fn(bool $nativeTypesPromoted): Type => new MixedType(), fn(TypeSpecifierContext $context, bool $nativeTypesPromoted) => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context), \false, \null, \null, \null, \null, \null, \null, \null, VariableFlow::sequence($exprResult->getVariableFlow(), VariableFlow::all(VariableFlow::READ_ALL), VariableFlow::throwing($throwPoint->getType(), \true)), Dependencies::merge($exprResult->getDependencies(), Dependencies::create($beforeScope->getFile(), [], [], [], $includedFiles)));
    }
}
