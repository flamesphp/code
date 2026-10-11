<?php

declare (strict_types=1);
namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\DependencyInjection\ValidatesStubFiles;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ExtendedFunctionVariant;
use PHPStan\Reflection\MethodPrototypeReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\MixedType;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function count;
use function is_bool;
use function sprintf;
use function strtolower;
/**
 * @implements Rule<InClassMethodNode>
 */
#[RegisteredRule(0)]
#[ValidatesStubFiles]
final class OverridingMethodRule implements Rule
{
    private PhpVersion $phpVersion;
    private \PHPStan\Rules\Methods\MethodSignatureRule $methodSignatureRule;
    private bool $checkPhpDocMethodSignatures;
    private \PHPStan\Rules\Methods\MethodParameterComparisonHelper $methodParameterComparisonHelper;
    private \PHPStan\Rules\Methods\MethodVisibilityComparisonHelper $methodVisibilityComparisonHelper;
    private \PHPStan\Rules\Methods\MethodPrototypeFinder $methodPrototypeFinder;
    private bool $checkMissingOverrideMethodAttribute;
    public function __construct(
        PhpVersion $phpVersion,
        \PHPStan\Rules\Methods\MethodSignatureRule $methodSignatureRule,
        #[AutowiredParameter]
        bool $checkPhpDocMethodSignatures,
        \PHPStan\Rules\Methods\MethodParameterComparisonHelper $methodParameterComparisonHelper,
        \PHPStan\Rules\Methods\MethodVisibilityComparisonHelper $methodVisibilityComparisonHelper,
        \PHPStan\Rules\Methods\MethodPrototypeFinder $methodPrototypeFinder,
        #[AutowiredParameter]
        bool $checkMissingOverrideMethodAttribute
    )
    {
        $this->phpVersion = $phpVersion;
        $this->methodSignatureRule = $methodSignatureRule;
        $this->checkPhpDocMethodSignatures = $checkPhpDocMethodSignatures;
        $this->methodParameterComparisonHelper = $methodParameterComparisonHelper;
        $this->methodVisibilityComparisonHelper = $methodVisibilityComparisonHelper;
        $this->methodPrototypeFinder = $methodPrototypeFinder;
        $this->checkMissingOverrideMethodAttribute = $checkMissingOverrideMethodAttribute;
    }
    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }
    /**
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\NodeCallbackInvoker&\PHPStan\Analyser\CollectedDataEmitter&\PHPStan\Analyser\DependencyTracker $scope
     */
    public function processNode(Node $node, \PHPStan\Analyser\Scope $scope): array
    {
        $method = $node->methodReflection;
        $prototypeData = $this->methodPrototypeFinder->findPrototype($node->classReflection, $method->getName());
        if ($prototypeData === null) {
            if (strtolower($method->getName()) === '__construct') {
                $parent = $method->declaringClass->getParentClass();
                if ($parent !== null && $parent->hasConstructor()) {
                    $parentConstructor = $parent->getConstructor();
                    if ($parentConstructor->isFinalByKeyword()->yes()) {
                        return $this->addErrors([RuleErrorBuilder::message(sprintf('Method %s::%s() overrides final method %s::%s().', $method->declaringClass->getDisplayName(), $method->getName(), $parent->getDisplayName(\true), $parentConstructor->getName()))->nonIgnorable()->identifier('method.parentMethodFinal')->build()], $node, $scope);
                    }
                    if ($parentConstructor->isFinal()->yes()) {
                        return $this->addErrors([RuleErrorBuilder::message(sprintf('Method %s::%s() overrides @final method %s::%s().', $method->declaringClass->getDisplayName(), $method->getName(), $parent->getDisplayName(\true), $parentConstructor->getName()))->identifier('method.parentMethodFinalByPhpDoc')->build()], $node, $scope);
                    }
                    $parentAbstract = $parentConstructor->isAbstract();
                    if ($method->isAbstract()->yes() && !(is_bool($parentAbstract) ? $parentAbstract : $parentAbstract->yes())) {
                        return $this->addErrors([RuleErrorBuilder::message(sprintf('Cannot make non-abstract method %s::%s() abstract in class %s.', $parentConstructor->getDeclaringClass()->getDisplayName(\true), $parentConstructor->getName(), $method->declaringClass->getDisplayName()))->nonIgnorable()->identifier('method.abstractOverridingNonAbstract')->build()], $node, $scope);
                    }
                }
            }
            if ($this->hasOverrideAttribute($node->originalNode)) {
                return [RuleErrorBuilder::message(sprintf('Method %s::%s() has #[\Override] attribute but does not override any method.', $method->declaringClass->getDisplayName(), $method->getName()))->nonIgnorable()->identifier('method.override')->fixNode($node->originalNode, function (Node\Stmt\ClassMethod $method) {
                    $method->attrGroups = $this->filterOverrideAttribute($method->attrGroups);
                    return $method;
                })->build()];
            }
            return [];
        }
        [$prototype, $prototypeDeclaringClass, $checkVisibility, $inheritancePrototype, $inheritancePrototypeDeclaringClass] = $prototypeData;
        $messages = [];
        if ($this->checkMissingOverrideMethodAttribute && !$scope->isInTrait() && !$this->hasOverrideAttribute($node->originalNode)) {
            $messages[] = RuleErrorBuilder::message(sprintf('Method %s::%s() overrides method %s::%s() but is missing the #[\Override] attribute.', $method->declaringClass->getDisplayName(), $method->getName(), $inheritancePrototypeDeclaringClass->getDisplayName(\true), $inheritancePrototype->getName()))->identifier('method.missingOverride')->fixNode($node->originalNode, static function (Node\Stmt\ClassMethod $method) {
                $method->attrGroups[] = new Node\AttributeGroup([new Attribute(new Node\Name\FullyQualified('Override'))]);
                return $method;
            })->build();
        }
        if ($inheritancePrototype->isFinalByKeyword()->yes()) {
            $messages[] = RuleErrorBuilder::message(sprintf('Method %s::%s() overrides final method %s::%s().', $method->declaringClass->getDisplayName(), $method->getName(), $inheritancePrototypeDeclaringClass->getDisplayName(\true), $inheritancePrototype->getName()))->nonIgnorable()->identifier('method.parentMethodFinal')->build();
        } elseif ($inheritancePrototype->isFinal()->yes()) {
            $messages[] = RuleErrorBuilder::message(sprintf('Method %s::%s() overrides @final method %s::%s().', $method->declaringClass->getDisplayName(), $method->getName(), $inheritancePrototypeDeclaringClass->getDisplayName(\true), $inheritancePrototype->getName()))->identifier('method.parentMethodFinalByPhpDoc')->build();
        }
        if ($prototype->isStatic()) {
            if (!$method->isStatic()) {
                $messages[] = RuleErrorBuilder::message(sprintf('Non-static method %s::%s() overrides static method %s::%s().', $method->declaringClass->getDisplayName(), $method->getName(), $prototypeDeclaringClass->getDisplayName(\true), $prototype->getName()))->nonIgnorable()->identifier('method.nonStatic')->build();
            }
        } elseif ($method->isStatic()) {
            $messages[] = RuleErrorBuilder::message(sprintf('Static method %s::%s() overrides non-static method %s::%s().', $method->declaringClass->getDisplayName(), $method->getName(), $prototypeDeclaringClass->getDisplayName(\true), $prototype->getName()))->nonIgnorable()->identifier('method.static')->build();
        }
        $prototypeAbstract = $prototype->isAbstract();
        if ($method->isAbstract()->yes() && !(is_bool($prototypeAbstract) ? $prototypeAbstract : $prototypeAbstract->yes())) {
            $messages[] = RuleErrorBuilder::message(sprintf('Cannot make non-abstract method %s::%s() abstract in class %s.', $prototypeDeclaringClass->getDisplayName(\true), $prototype->getName(), $method->declaringClass->getDisplayName()))->nonIgnorable()->identifier('method.abstractOverridingNonAbstract')->build();
        }
        if ($checkVisibility) {
            $messages = array_merge($messages, $this->methodVisibilityComparisonHelper->compare($prototype, $prototypeDeclaringClass, $method, $node->originalNode));
        }
        $prototypeVariants = $prototype->getVariants();
        if (count($prototypeVariants) !== 1) {
            return $this->addErrors($messages, $node, $scope);
        }
        $prototypeVariant = $prototypeVariants[0];
        $methodReturnType = $method->getNativeReturnType();
        $realPrototype = $method->getPrototype();
        if ($realPrototype instanceof MethodPrototypeReflection && $this->phpVersion->versionId >= 80100 && $realPrototype->tentativeReturnType !== null && !$this->hasReturnTypeWillChangeAttribute($node->originalNode) && count($prototypeDeclaringClass->getNativeReflection()->getMethod($prototype->getName())->getAttributes('ReturnTypeWillChange')) === 0) {
            if (!$this->methodParameterComparisonHelper->isReturnTypeCompatible($realPrototype->tentativeReturnType, $method->getNativeReturnType(), \true)) {
                $messages[] = RuleErrorBuilder::message(sprintf('Return type %s of method %s::%s() is not covariant with tentative return type %s of method %s::%s().', $methodReturnType->describe(VerbosityLevel::typeOnly()), $method->declaringClass->getDisplayName(), $method->getName(), $realPrototype->tentativeReturnType->describe(VerbosityLevel::typeOnly()), $realPrototype->declaringClass->getDisplayName(\true), $realPrototype->name))->tip('Make it covariant, or use the #[\ReturnTypeWillChange] attribute to temporarily suppress the error.')->nonIgnorable()->identifier('method.tentativeReturnType')->build();
            }
        }
        $messages = array_merge($messages, $this->methodParameterComparisonHelper->compare($prototype, $prototypeDeclaringClass, $method, $scope, \false));
        if (!$prototypeVariant instanceof ExtendedFunctionVariant) {
            return $this->addErrors($messages, $node, $scope);
        }
        $prototypeReturnType = $prototypeVariant->getNativeReturnType();
        $reportReturnType = \true;
        if ($this->phpVersion->versionId >= 80100) {
            $reportReturnType = !$realPrototype instanceof MethodPrototypeReflection || $realPrototype->tentativeReturnType === null || (is_bool($prototype->isBuiltin()) ? !$prototype->isBuiltin() : $prototype->isBuiltin()->no());
        } else if ($realPrototype instanceof MethodPrototypeReflection && $realPrototype->isInternal) {
            if ((is_bool($prototype->isBuiltin()) ? $prototype->isBuiltin() : $prototype->isBuiltin()->yes()) && $prototypeDeclaringClass->getName() !== $realPrototype->declaringClass->getName()) {
                $realPrototypeVariant = $realPrototype->variants[0];
                if ($prototypeReturnType instanceof MixedType && !$prototypeReturnType->isExplicitMixed() && (!$realPrototypeVariant->getReturnType() instanceof MixedType || $realPrototypeVariant->getReturnType()->isExplicitMixed())) {
                    $reportReturnType = \false;
                }
            }
            if ($reportReturnType && (is_bool($prototype->isBuiltin()) ? $prototype->isBuiltin() : $prototype->isBuiltin()->yes())) {
                $reportReturnType = !$this->hasReturnTypeWillChangeAttribute($node->originalNode);
            }
        }
        if ($reportReturnType && !$this->methodParameterComparisonHelper->isReturnTypeCompatible($prototypeReturnType, $methodReturnType, $this->phpVersion->versionId >= 70400)) {
            if ($this->phpVersion->versionId >= 70400) {
                $messages[] = RuleErrorBuilder::message(sprintf('Return type %s of method %s::%s() is not covariant with return type %s of method %s::%s().', $methodReturnType->describe(VerbosityLevel::typeOnly()), $method->declaringClass->getDisplayName(), $method->getName(), $prototypeReturnType->describe(VerbosityLevel::typeOnly()), $prototypeDeclaringClass->getDisplayName(\true), $prototype->getName()))->nonIgnorable()->identifier('method.childReturnType')->build();
            } else {
                $messages[] = RuleErrorBuilder::message(sprintf('Return type %s of method %s::%s() is not compatible with return type %s of method %s::%s().', $methodReturnType->describe(VerbosityLevel::typeOnly()), $method->declaringClass->getDisplayName(), $method->getName(), $prototypeReturnType->describe(VerbosityLevel::typeOnly()), $prototypeDeclaringClass->getDisplayName(\true), $prototype->getName()))->nonIgnorable()->identifier('method.childReturnType')->build();
            }
        }
        return $this->addErrors($messages, $node, $scope);
    }
    /**
     * @param Node\AttributeGroup[] $attrGroups
     * @return Node\AttributeGroup[]
     */
    private function filterOverrideAttribute(array $attrGroups): array
    {
        foreach ($attrGroups as $i => $attrGroup) {
            foreach ($attrGroup->attrs as $j => $attr) {
                if (strtolower($attr->name->name) !== 'override') {
                    continue;
                }
                unset($attrGroup->attrs[$j]);
                if (count($attrGroup->attrs) !== 0) {
                    continue;
                }
                unset($attrGroups[$i]);
            }
        }
        return $attrGroups;
    }
    /**
     * @param list<IdentifierRuleError> $errors
     * @return list<IdentifierRuleError>
     * @param \PHPStan\Analyser\Scope&\PHPStan\Analyser\NodeCallbackInvoker&\PHPStan\Analyser\CollectedDataEmitter&\PHPStan\Analyser\DependencyTracker $scope
     */
    private function addErrors(array $errors, InClassMethodNode $classMethod, \PHPStan\Analyser\Scope $scope): array
    {
        if (count($errors) > 0) {
            return $errors;
        }
        if (!$this->checkPhpDocMethodSignatures) {
            return $errors;
        }
        return $this->methodSignatureRule->processNode($classMethod, $scope);
    }
    private function hasReturnTypeWillChangeAttribute(Node\Stmt\ClassMethod $method): bool
    {
        foreach ($method->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if (strtolower($attr->name->name) === 'returntypewillchange') {
                    return \true;
                }
            }
        }
        return \false;
    }
    private function hasOverrideAttribute(Node\Stmt\ClassMethod $method): bool
    {
        foreach ($method->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if (strtolower($attr->name->name) === 'override') {
                    return \true;
                }
            }
        }
        return \false;
    }
}
