<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Generics\TemplateArgumentFrame;
use PHPStan\Node\InvalidateExprNode;
use PHPStan\Reflection\Callables\CallableParametersAcceptor;
use PHPStan\Reflection\Callables\SimpleImpurePoint;
use PHPStan\Reflection\Callables\SimpleThrowPoint;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\Type;
final class ResolvedFunctionVariantWithCallable implements \PHPStan\Reflection\ResolvedFunctionVariant, CallableParametersAcceptor
{
    private \PHPStan\Reflection\ResolvedFunctionVariant $parametersAcceptor;
    /**
     * @var SimpleThrowPoint[]
     */
    private array $throwPoints;
    private TrinaryLogic $isPure;
    /**
     * @var SimpleImpurePoint[]
     */
    private array $impurePoints;
    /**
     * @var InvalidateExprNode[]
     */
    private array $invalidateExpressions;
    /**
     * @var string[]
     */
    private array $usedVariables;
    private TrinaryLogic $acceptsNamedArguments;
    private TrinaryLogic $mustUseReturnValue;
    private ?\PHPStan\Reflection\Assertions $assertions;
    private ?TrinaryLogic $isStatic;
    /**
     * @param SimpleThrowPoint[] $throwPoints
     * @param SimpleImpurePoint[] $impurePoints
     * @param InvalidateExprNode[] $invalidateExpressions
     * @param string[] $usedVariables
     */
    public function __construct(\PHPStan\Reflection\ResolvedFunctionVariant $parametersAcceptor, array $throwPoints, TrinaryLogic $isPure, array $impurePoints, array $invalidateExpressions, array $usedVariables, TrinaryLogic $acceptsNamedArguments, TrinaryLogic $mustUseReturnValue, ?\PHPStan\Reflection\Assertions $assertions = null, ?TrinaryLogic $isStatic = null)
    {
        $this->parametersAcceptor = $parametersAcceptor;
        $this->throwPoints = $throwPoints;
        $this->isPure = $isPure;
        $this->impurePoints = $impurePoints;
        $this->invalidateExpressions = $invalidateExpressions;
        $this->usedVariables = $usedVariables;
        $this->acceptsNamedArguments = $acceptsNamedArguments;
        $this->mustUseReturnValue = $mustUseReturnValue;
        $this->assertions = $assertions;
        $this->isStatic = $isStatic;
    }
    public function getOriginalParametersAcceptor(): \PHPStan\Reflection\ParametersAcceptor
    {
        return $this->parametersAcceptor->getOriginalParametersAcceptor();
    }
    public function getTemplateTypeMap(): TemplateTypeMap
    {
        return $this->parametersAcceptor->getTemplateTypeMap();
    }
    public function getResolvedTemplateTypeMap(): TemplateTypeMap
    {
        return $this->parametersAcceptor->getResolvedTemplateTypeMap();
    }
    public function getCallSiteVarianceMap(): TemplateTypeVarianceMap
    {
        return $this->parametersAcceptor->getCallSiteVarianceMap();
    }
    public function getParameters(): array
    {
        return $this->parametersAcceptor->getParameters();
    }
    public function isVariadic(): bool
    {
        return $this->parametersAcceptor->isVariadic();
    }
    public function getReturnTypeWithUnresolvableTemplateTypes(): Type
    {
        return $this->parametersAcceptor->getReturnTypeWithUnresolvableTemplateTypes();
    }
    public function getReturnTypeWithUnresolvedTemplateArguments(Expr $site, TemplateArgumentFrame $frame, bool $allowUnresolved): Type
    {
        return $this->parametersAcceptor->getReturnTypeWithUnresolvedTemplateArguments($site, $frame, $allowUnresolved);
    }
    public function getReturnType(): Type
    {
        return $this->parametersAcceptor->getReturnType();
    }
    public function hasBoundArgs(): bool
    {
        return $this->parametersAcceptor->hasBoundArgs();
    }
    public function resolveConditionalTypes(Type $type): Type
    {
        return $this->parametersAcceptor->resolveConditionalTypes($type);
    }
    public function getPhpDocReturnType(): Type
    {
        return $this->parametersAcceptor->getPhpDocReturnType();
    }
    public function getNativeReturnType(): Type
    {
        return $this->parametersAcceptor->getNativeReturnType();
    }
    public function getThrowPoints(): array
    {
        return $this->throwPoints;
    }
    public function isPure(): TrinaryLogic
    {
        return $this->isPure;
    }
    public function getImpurePoints(): array
    {
        return $this->impurePoints;
    }
    public function getInvalidateExpressions(): array
    {
        return $this->invalidateExpressions;
    }
    public function getUsedVariables(): array
    {
        return $this->usedVariables;
    }
    public function acceptsNamedArguments(): TrinaryLogic
    {
        return $this->acceptsNamedArguments;
    }
    public function mustUseReturnValue(): TrinaryLogic
    {
        return $this->mustUseReturnValue;
    }
    public function getAsserts(): \PHPStan\Reflection\Assertions
    {
        return $this->assertions ?? \PHPStan\Reflection\Assertions::createEmpty();
    }
    public function isStaticClosure(): TrinaryLogic
    {
        return $this->isStatic ?? TrinaryLogic::createMaybe();
    }
}
