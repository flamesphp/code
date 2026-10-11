<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use Closure;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPStan\Node\Expr\PropertyInitializationExpr;
use PHPStan\Rules\Properties\FoundPropertyReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\NeverType;
/**
 * The inside-out carrier for isset/empty/?? chains. Each chain-link
 * ExpressionResult (variable / array dim fetch / property fetch) holds the
 * descriptor for its own link plus references to the child ExpressionResult(s),
 * built during the single pass like containsNullsafe.
 *
 * resolve() walks the chain once on the asking scope and produces an
 * IssetabilityResolution of fully-resolved IssetabilityLinkInfo facts. The engine
 * (IssetabilityResolution::isSet) and the rule (PHPStan\Rules\IssetCheck) read
 * those facts; neither re-traverses the AST nor re-resolves types/reflections.
 */
#[ShadowedByTurboExtension(__DIR__ . '/../turbo-ext/src/IssetabilityDescriptor.cpp')]
final class IssetabilityDescriptor
{
    private string $kind;
    private ?string $variableName;
    private ?\PHPStan\Analyser\ExpressionResult $varResult;
    private ?\PHPStan\Analyser\ExpressionResult $dimResult;
    private ?\PHPStan\Analyser\ExpressionResult $innerResult;
    /**
     * @var Closure(MutatingScope): ?FoundPropertyReflection|null
     */
    private ?Closure $reflectionResolver;
    /**
     * @var PropertyFetch|StaticPropertyFetch|null
     */
    private ?Expr $propertyFetch;
    private const KIND_VARIABLE = 'variable';
    private const KIND_OFFSET = 'offset';
    private const KIND_PROPERTY = 'property';
    /**
     * @param Closure(MutatingScope): ?FoundPropertyReflection|null $reflectionResolver
     * @param PropertyFetch|StaticPropertyFetch|null $propertyFetch
     */
    private function __construct(string $kind, ?string $variableName = null, ?\PHPStan\Analyser\ExpressionResult $varResult = null, ?\PHPStan\Analyser\ExpressionResult $dimResult = null, ?\PHPStan\Analyser\ExpressionResult $innerResult = null, ?Closure $reflectionResolver = null, ?Expr $propertyFetch = null)
    {
        $this->kind = $kind;
        $this->variableName = $variableName;
        $this->varResult = $varResult;
        $this->dimResult = $dimResult;
        $this->innerResult = $innerResult;
        $this->reflectionResolver = $reflectionResolver;
        $this->propertyFetch = $propertyFetch;
    }
    public static function variable(string $variableName): self
    {
        return new self(self::KIND_VARIABLE, $variableName);
    }
    public static function offset(\PHPStan\Analyser\ExpressionResult $varResult, \PHPStan\Analyser\ExpressionResult $dimResult): self
    {
        return new self(self::KIND_OFFSET, \null, $varResult, $dimResult);
    }
    /**
     * @param Closure(MutatingScope): ?FoundPropertyReflection $reflectionResolver
     * @param PropertyFetch|StaticPropertyFetch $propertyFetch
     */
    public static function property(?\PHPStan\Analyser\ExpressionResult $innerResult, Closure $reflectionResolver, Expr $propertyFetch): self
    {
        return new self(self::KIND_PROPERTY, \null, \null, \null, $innerResult, $reflectionResolver, $propertyFetch);
    }
    /**
     * Walks the chain once on the asking scope, resolving every link's facts.
     * $expr is the expression this descriptor belongs to (threaded by
     * ExpressionResult::getIssetabilityResolution); $useNativeTypes selects native
     * vs phpdoc types (the rule's treatPhpDocTypesAsCertain).
     */
    public function resolve(\PHPStan\Analyser\MutatingScope $scope, bool $useNativeTypes, Expr $expr, bool $reprocessUntrackedLinks = \false): \PHPStan\Analyser\IssetabilityResolution
    {
        if ($this->kind === self::KIND_VARIABLE) {
            $variableName = $this->variableName;
            if ($variableName === null) {
                throw new ShouldNotHappenException();
            }
            $hasVariable = $scope->hasVariableType($variableName);
            $valueType = $hasVariable->yes() ? $useNativeTypes ? $scope->doNotTreatPhpDocTypesAsCertain()->getVariableType($variableName) : $scope->getVariableType($variableName) : new NeverType();
            return new \PHPStan\Analyser\IssetabilityResolution(\PHPStan\Analyser\IssetabilityLinkInfo::variable($variableName, $hasVariable, $valueType), null);
        }
        if ($this->kind === self::KIND_OFFSET) {
            $varResult = $this->varResult;
            $dimResult = $this->dimResult;
            if ($varResult === null || $dimResult === null) {
                throw new ShouldNotHappenException();
            }
            // A rule-facing resolution re-prices untracked links on the asking
            // scope: the stored results were priced under the coalesce walk's
            // ensured non-nullability (NonNullabilityHelper), so their own types
            // have null already stripped - folding the isset verdict from them
            // would be circular. Engine-side folds keep the stored reads (their
            // scopes track the ensured links; re-pricing there would walk).
            $reprocessVar = $reprocessUntrackedLinks && !$scope->hasExpressionType($varResult->getExpr())->yes();
            $varType = $reprocessVar ? $useNativeTypes ? $scope->doNotTreatPhpDocTypesAsCertain()->getNativeType($varResult->getExpr()) : $scope->getType($varResult->getExpr()) : $varResult->getTypeOnScope($scope, $useNativeTypes);
            $dimType = $dimResult->getTypeOnScope($scope, $useNativeTypes);
            $hasOffsetValue = $varType->hasOffsetValueType($dimType);
            $valueType = $hasOffsetValue->no() ? new NeverType() : $varType->getOffsetValueType($dimType);
            return new \PHPStan\Analyser\IssetabilityResolution(\PHPStan\Analyser\IssetabilityLinkInfo::offset($varType->isOffsetAccessible(), $hasOffsetValue, $scope->hasExpressionType($expr)->yes(), $varType, $dimType, $valueType), $varResult->getIssetabilityResolution($scope, $useNativeTypes, $reprocessUntrackedLinks));
        }
        $reflectionResolver = $this->reflectionResolver;
        $propertyFetch = $this->propertyFetch;
        if ($reflectionResolver === null || $propertyFetch === null) {
            throw new ShouldNotHappenException();
        }
        $inner = $this->innerResult !== null ? $this->innerResult->getIssetabilityResolution($scope, $useNativeTypes, $reprocessUntrackedLinks) : null;
        $propertyReflection = $reflectionResolver($scope);
        if ($propertyReflection === null) {
            return new \PHPStan\Analyser\IssetabilityResolution(\PHPStan\Analyser\IssetabilityLinkInfo::property(null, $propertyFetch, \false, \false, TrinaryLogic::createNo(), new NeverType(), new NeverType(), \false, \false, \false, \false, \false, \false, \false), $inner);
        }
        $hasNativeType = $propertyReflection->originalPropertyReflection->hasNativeType();
        $nativeReflection = $propertyReflection->getNativeReflection();
        $initializedThisProperty = $propertyFetch instanceof PropertyFetch && $propertyFetch->name instanceof Identifier && $propertyFetch->var instanceof Variable && $propertyFetch->var->name === 'this' && $scope->hasExpressionType(new PropertyInitializationExpr($propertyReflection->propertyName))->yes();
        return new \PHPStan\Analyser\IssetabilityResolution(\PHPStan\Analyser\IssetabilityLinkInfo::property($propertyReflection, $propertyFetch, $propertyReflection->getNativeReflection() !== null, $hasNativeType, $propertyReflection->originalPropertyReflection->isVirtual(), $propertyReflection->writableType, $hasNativeType ? $propertyReflection->originalPropertyReflection->getNativeType() : new NeverType(), $scope->hasExpressionType($propertyFetch)->yes(), $initializedThisProperty, $nativeReflection !== null, $nativeReflection !== null && $nativeReflection->isPromoted(), $nativeReflection !== null && $nativeReflection->isReadOnly(), $nativeReflection !== null && $nativeReflection->isHooked(), $nativeReflection !== null && $nativeReflection->getNativeReflection()->betterReflectionProperty->hasDefaultValue()), $inner);
    }
}
