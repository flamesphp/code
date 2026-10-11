<?php

declare (strict_types=1);
namespace PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\UnionType;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Printer\NodeTypePrinter;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NonexistentParentClassType;
use PHPStan\Type\ParserNodeTypeToPHPStanType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\VerbosityLevel;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_slice;
use function count;
use function implode;
use function in_array;
use function is_string;
use function sprintf;
use function strtolower;
#[AutowiredService]
final class FunctionDefinitionCheck
{
    private ReflectionProvider $reflectionProvider;
    private \PHPStan\Rules\ClassNameCheck $classCheck;
    private UnresolvableTypeHelper $unresolvableTypeHelper;
    private PhpVersion $phpVersion;
    private bool $checkClassCaseSensitivity;
    private bool $checkThisOnly;
    private bool $checkImportedClassNameCase;
    public function __construct(
        ReflectionProvider $reflectionProvider,
        \PHPStan\Rules\ClassNameCheck $classCheck,
        UnresolvableTypeHelper $unresolvableTypeHelper,
        PhpVersion $phpVersion,
        #[AutowiredParameter]
        bool $checkClassCaseSensitivity,
        #[AutowiredParameter]
        bool $checkThisOnly,
        #[AutowiredParameter('%featureToggles.checkImportedClassNameCase%')]
        bool $checkImportedClassNameCase
    )
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->classCheck = $classCheck;
        $this->unresolvableTypeHelper = $unresolvableTypeHelper;
        $this->phpVersion = $phpVersion;
        $this->checkClassCaseSensitivity = $checkClassCaseSensitivity;
        $this->checkThisOnly = $checkThisOnly;
        $this->checkImportedClassNameCase = $checkImportedClassNameCase;
    }
    /**
     * @return list<IdentifierRuleError>
     */
    public function checkFunction(Scope $scope, Function_ $function, PhpFunctionFromParserNodeReflection $functionReflection, string $parameterMessage, string $returnMessage, string $unionTypesMessage, string $templateTypeMissingInParameterMessage, string $unresolvableParameterTypeMessage, string $unresolvableReturnTypeMessage, string $noDiscardVoidReturnMessage): array
    {
        return $this->checkParametersAcceptor($scope, $functionReflection, $function, $parameterMessage, $returnMessage, $unionTypesMessage, $templateTypeMissingInParameterMessage, $unresolvableParameterTypeMessage, $unresolvableReturnTypeMessage, $noDiscardVoidReturnMessage);
    }
    /**
     * @param Node\Param[] $parameters
     * @param Node\Identifier|Node\Name|Node\ComplexType|null $returnTypeNode
     * @param Node\AttributeGroup[] $attribGroups
     * @return list<IdentifierRuleError>
     */
    public function checkAnonymousFunction(Scope $scope, array $parameters, $returnTypeNode, array $attribGroups, string $parameterMessage, string $returnMessage, string $unionTypesMessage, string $unresolvableParameterTypeMessage, string $unresolvableReturnTypeMessage, string $noDiscardReturnTypeMessage): array
    {
        $errors = [];
        $unionTypeReported = \false;
        foreach ($parameters as $i => $param) {
            if ($param->type === null) {
                continue;
            }
            if (!$unionTypeReported && $param->type instanceof UnionType && !$scope->getPhpVersion()->supportsNativeUnionTypes()->yes()) {
                $errors[] = \PHPStan\Rules\RuleErrorBuilder::message($unionTypesMessage)->line($param->getStartLine())->identifier('parameter.unionTypeNotSupported')->nonIgnorable()->build();
                $unionTypeReported = \true;
            }
            if (!$param->var instanceof Variable || !is_string($param->var->name)) {
                throw new ShouldNotHappenException();
            }
            $implicitlyNullableTypeError = $this->checkImplicitlyNullableType($param->type, $param->default, $i + 1, $param->getStartLine(), $param->var->name);
            if ($implicitlyNullableTypeError !== null) {
                $errors[] = $implicitlyNullableTypeError;
            }
            $type = $scope->getFunctionType($param->type, \false, \false);
            $unresolvableType = $this->phpVersion->versionId >= 80100 ? $this->unresolvableTypeHelper->getUnresolvableType($type) : null;
            if ($unresolvableType !== null) {
                $errorBuilder = \PHPStan\Rules\RuleErrorBuilder::message(sprintf($unresolvableParameterTypeMessage, $param->var->name))->line($param->type->getStartLine())->identifier('parameter.unresolvableNativeType')->nonIgnorable();
                foreach ($unresolvableType->reasons as $reason) {
                    $errorBuilder->addTip($reason);
                }
                $errors[] = $errorBuilder->build();
            }
            foreach ($type->getReferencedClasses() as $class) {
                if (!$this->reflectionProvider->hasClass($class)) {
                    $errors[] = \PHPStan\Rules\RuleErrorBuilder::message(sprintf($parameterMessage, $param->var->name, $class))->line($param->type->getStartLine())->identifier('class.notFound')->build();
                    continue;
                }
                $classReflection = $this->reflectionProvider->getClass($class);
                if ($classReflection->isTrait()) {
                    $errors[] = \PHPStan\Rules\RuleErrorBuilder::message(sprintf($parameterMessage, $param->var->name, $class))->line($param->type->getStartLine())->identifier('parameter.trait')->build();
                    continue;
                }
            }
            $anonParamOriginalCasePairs = $this->getOriginalClassNamePairsFromTypeNode($param->type);
            $errors = array_merge($errors, $this->classCheck->checkClassNames($scope, array_map(static function (string $class) use ($param, $anonParamOriginalCasePairs): \PHPStan\Rules\ClassNameNodePair {
                $lowerClass = strtolower($class);
                if (isset($anonParamOriginalCasePairs[$lowerClass])) {
                    return $anonParamOriginalCasePairs[$lowerClass];
                }
                return new \PHPStan\Rules\ClassNameNodePair($class, $param->type);
            }, $type->getReferencedClasses()), \PHPStan\Rules\ClassNameUsageLocation::from(\PHPStan\Rules\ClassNameUsageLocation::PARAMETER_TYPE, ['parameterName' => $param->var->name, 'isInAnonymousFunction' => \true]), $this->checkClassCaseSensitivity));
        }
        if ($this->phpVersion->versionId >= 80000) {
            $errors = array_merge($errors, $this->checkRequiredParameterAfterOptional($parameters));
        }
        if ($returnTypeNode === null) {
            return $errors;
        }
        if ($returnTypeNode instanceof Identifier && in_array(strtolower($returnTypeNode->name), ['void', 'never'], \true)) {
            foreach ($attribGroups as $attribGroup) {
                foreach ($attribGroup->attrs as $attrib) {
                    if (strtolower($attrib->name->name) === 'nodiscard') {
                        $errors[] = \PHPStan\Rules\RuleErrorBuilder::message(sprintf($noDiscardReturnTypeMessage, $returnTypeNode->name))->line($returnTypeNode->getStartLine())->identifier('attribute.target')->build();
                        break 2;
                    }
                }
            }
        }
        if (!$unionTypeReported && $returnTypeNode instanceof UnionType && !$scope->getPhpVersion()->supportsNativeUnionTypes()->yes()) {
            $errors[] = \PHPStan\Rules\RuleErrorBuilder::message($unionTypesMessage)->line($returnTypeNode->getStartLine())->identifier('return.unionTypeNotSupported')->nonIgnorable()->build();
        }
        $returnType = $scope->getFunctionType($returnTypeNode, \false, \false);
        $unresolvableReturnType = $this->phpVersion->versionId >= 80100 ? $this->unresolvableTypeHelper->getUnresolvableType($returnType) : null;
        if ($unresolvableReturnType !== null) {
            $errorBuilder = \PHPStan\Rules\RuleErrorBuilder::message($unresolvableReturnTypeMessage)->line($returnTypeNode->getStartLine())->identifier('return.unresolvableNativeType')->nonIgnorable();
            foreach ($unresolvableReturnType->reasons as $reason) {
                $errorBuilder->addTip($reason);
            }
            $errors[] = $errorBuilder->build();
        }
        foreach ($returnType->getReferencedClasses() as $returnTypeClass) {
            if (!$this->reflectionProvider->hasClass($returnTypeClass)) {
                $errors[] = \PHPStan\Rules\RuleErrorBuilder::message(sprintf($returnMessage, $returnTypeClass))->line($returnTypeNode->getStartLine())->identifier('class.notFound')->build();
                continue;
            }
            if ($this->reflectionProvider->getClass($returnTypeClass)->isTrait()) {
                $errors[] = \PHPStan\Rules\RuleErrorBuilder::message(sprintf($returnMessage, $returnTypeClass))->line($returnTypeNode->getStartLine())->identifier('return.trait')->build();
                continue;
            }
        }
        $anonReturnOriginalCasePairs = $this->getOriginalClassNamePairsFromTypeNode($returnTypeNode);
        $errors = array_merge($errors, $this->classCheck->checkClassNames($scope, array_map(static function (string $class) use ($returnTypeNode, $anonReturnOriginalCasePairs): \PHPStan\Rules\ClassNameNodePair {
            $lowerClass = strtolower($class);
            if (isset($anonReturnOriginalCasePairs[$lowerClass])) {
                return $anonReturnOriginalCasePairs[$lowerClass];
            }
            return new \PHPStan\Rules\ClassNameNodePair($class, $returnTypeNode);
        }, $returnType->getReferencedClasses()), \PHPStan\Rules\ClassNameUsageLocation::from(\PHPStan\Rules\ClassNameUsageLocation::RETURN_TYPE, ['isInAnonymousFunction' => \true]), $this->checkClassCaseSensitivity));
        return $errors;
    }
    /**
     * @return list<IdentifierRuleError>
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\PropertyHook $methodNode
     */
    public function checkClassMethod(Scope $scope, PhpMethodFromParserNodeReflection $methodReflection, $methodNode, string $parameterMessage, string $returnMessage, string $unionTypesMessage, string $templateTypeMissingInParameterMessage, string $unresolvableParameterTypeMessage, string $unresolvableReturnTypeMessage, string $selfOutMessage, string $noDiscardVoidReturnMessage): array
    {
        $errors = $this->checkParametersAcceptor($scope, $methodReflection, $methodNode, $parameterMessage, $returnMessage, $unionTypesMessage, $templateTypeMissingInParameterMessage, $unresolvableParameterTypeMessage, $unresolvableReturnTypeMessage, $noDiscardVoidReturnMessage);
        $selfOutType = $methodReflection->selfOutType;
        if ($selfOutType !== null) {
            $selfOutTypeReferencedClasses = $selfOutType->getReferencedClasses();
            foreach ($selfOutTypeReferencedClasses as $class) {
                if (!$this->reflectionProvider->hasClass($class)) {
                    $errors[] = \PHPStan\Rules\RuleErrorBuilder::message(sprintf($selfOutMessage, $class))->line($methodNode->getStartLine())->identifier('class.notFound')->build();
                    continue;
                }
                if (!$this->reflectionProvider->getClass($class)->isTrait()) {
                    continue;
                }
                $errors[] = \PHPStan\Rules\RuleErrorBuilder::message(sprintf($selfOutMessage, $class))->line($methodNode->getStartLine())->identifier('selfOut.trait')->build();
            }
            $errors = array_merge($errors, $this->classCheck->checkClassNames($scope, array_map(static fn(string $class): \PHPStan\Rules\ClassNameNodePair => new \PHPStan\Rules\ClassNameNodePair($class, $methodNode), $selfOutTypeReferencedClasses), \PHPStan\Rules\ClassNameUsageLocation::from(\PHPStan\Rules\ClassNameUsageLocation::PHPDOC_TAG_SELF_OUT), $this->checkClassCaseSensitivity));
        }
        return $errors;
    }
    /**
     * @return list<IdentifierRuleError>
     * @param \PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection|\PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection $parametersAcceptor
     */
    private function checkParametersAcceptor(Scope $scope, $parametersAcceptor, FunctionLike $functionNode, string $parameterMessage, string $returnMessage, string $unionTypesMessage, string $templateTypeMissingInParameterMessage, string $unresolvableParameterTypeMessage, string $unresolvableReturnTypeMessage, string $noDiscardReturnTypeMessage): array
    {
        $errors = [];
        $parameterNodes = $functionNode->getParams();
        if (!$scope->getPhpVersion()->supportsNativeUnionTypes()->yes()) {
            $unionTypeReported = \false;
            foreach ($parameterNodes as $parameterNode) {
                if (!$parameterNode->type instanceof UnionType) {
                    continue;
                }
                $errors[] = \PHPStan\Rules\RuleErrorBuilder::message($unionTypesMessage)->line($parameterNode->getStartLine())->identifier('parameter.unionTypeNotSupported')->nonIgnorable()->build();
                $unionTypeReported = \true;
                break;
            }
            if (!$unionTypeReported && $functionNode->getReturnType() instanceof UnionType) {
                $errors[] = \PHPStan\Rules\RuleErrorBuilder::message($unionTypesMessage)->line($functionNode->getReturnType()->getStartLine())->identifier('return.unionTypeNotSupported')->nonIgnorable()->build();
            }
        }
        foreach ($parameterNodes as $i => $parameterNode) {
            if (!$parameterNode->var instanceof Variable || !is_string($parameterNode->var->name)) {
                throw new ShouldNotHappenException();
            }
            $implicitlyNullableTypeError = $this->checkImplicitlyNullableType($parameterNode->type, $parameterNode->default, $i + 1, $parameterNode->getStartLine(), $parameterNode->var->name);
            if ($implicitlyNullableTypeError === null) {
                continue;
            }
            $errors[] = $implicitlyNullableTypeError;
        }
        if ($this->phpVersion->versionId >= 80000) {
            $errors = array_merge($errors, $this->checkRequiredParameterAfterOptional($parameterNodes));
        }
        $returnTypeNode = $functionNode->getReturnType() ?? $functionNode;
        foreach ($parametersAcceptor->getParameters() as $parameter) {
            $referencedClasses = $this->getParameterReferencedClasses($parameter);
            $parameterNode = null;
            $parameterNodeCallback = function () use ($parameter, $parameterNodes, &$parameterNode): Param {
                if ($parameterNode === null) {
                    $parameterNode = $this->getParameterNode($parameter->getName(), $parameterNodes);
                }
                return $parameterNode;
            };
            $parameterVar = $parameterNodeCallback()->var;
            if (!$parameterVar instanceof Variable || !is_string($parameterVar->name)) {
                throw new ShouldNotHappenException();
            }
            $unresolvableType = $this->phpVersion->versionId >= 80100 ? $this->unresolvableTypeHelper->getUnresolvableType($parameter->getNativeType()) : null;
            if ($unresolvableType !== null) {
                $errorBuilder = \PHPStan\Rules\RuleErrorBuilder::message(sprintf($unresolvableParameterTypeMessage, $parameterVar->name))->line($parameterNodeCallback()->getStartLine())->identifier('parameter.unresolvableNativeType')->nonIgnorable();
                foreach ($unresolvableType->reasons as $reason) {
                    $errorBuilder->addTip($reason);
                }
                $errors[] = $errorBuilder->build();
            }
            foreach ($referencedClasses as $class) {
                if (!$this->reflectionProvider->hasClass($class)) {
                    $errors[] = \PHPStan\Rules\RuleErrorBuilder::message(sprintf($parameterMessage, $parameter->getName(), $class))->line($parameterNodeCallback()->getStartLine())->identifier('class.notFound')->build();
                    continue;
                }
                if (!$this->reflectionProvider->getClass($class)->isTrait()) {
                    continue;
                }
                $errors[] = \PHPStan\Rules\RuleErrorBuilder::message(sprintf($parameterMessage, $parameter->getName(), $class))->line($parameterNodeCallback()->getStartLine())->identifier('parameter.trait')->build();
            }
            $locationData = ['parameterName' => $parameter->getName()];
            if ($parametersAcceptor instanceof PhpMethodFromParserNodeReflection) {
                $locationData['method'] = $parametersAcceptor;
                if (!$parametersAcceptor->declaringClass->isAnonymous()) {
                    $locationData['currentClassName'] = $parametersAcceptor->declaringClass->getName();
                }
            } else {
                $locationData['function'] = $parametersAcceptor;
            }
            $originalCasePairs = $this->getOriginalClassNamePairsFromTypeNode($parameterNodeCallback()->type);
            $errors = array_merge($errors, $this->classCheck->checkClassNames($scope, array_map(static function (string $class) use ($parameterNodeCallback, $originalCasePairs): \PHPStan\Rules\ClassNameNodePair {
                $lowerClass = strtolower($class);
                if (isset($originalCasePairs[$lowerClass])) {
                    return $originalCasePairs[$lowerClass];
                }
                return new \PHPStan\Rules\ClassNameNodePair($class, $parameterNodeCallback());
            }, $referencedClasses), \PHPStan\Rules\ClassNameUsageLocation::from(\PHPStan\Rules\ClassNameUsageLocation::PARAMETER_TYPE, $locationData), $this->checkClassCaseSensitivity));
            if (!$parameter->getType() instanceof NonexistentParentClassType) {
                continue;
            }
            $errors[] = \PHPStan\Rules\RuleErrorBuilder::message(sprintf($parameterMessage, $parameter->getName(), $parameter->getType()->describe(VerbosityLevel::typeOnly())))->line($parameterNodeCallback()->getStartLine())->identifier('parameter.noParent')->build();
        }
        if ($this->phpVersion->versionId >= 80100 && $functionNode->getReturnType() !== null) {
            $nativeReturnType = ParserNodeTypeToPHPStanType::resolve($functionNode->getReturnType(), $scope->isInClass() ? $scope->getClassReflection() : null);
            $unresolvableType = $this->unresolvableTypeHelper->getUnresolvableType($nativeReturnType);
            if ($unresolvableType !== null) {
                $errorBuilder = \PHPStan\Rules\RuleErrorBuilder::message($unresolvableReturnTypeMessage)->nonIgnorable()->line($returnTypeNode->getStartLine())->identifier('return.unresolvableNativeType');
                foreach ($unresolvableType->reasons as $reason) {
                    $errorBuilder->addTip($reason);
                }
                $errors[] = $errorBuilder->build();
            }
        }
        if ($parametersAcceptor->mustUseReturnValue()->yes()) {
            $returnType = $parametersAcceptor->getReturnType();
            if ($returnType->isVoid()->yes() || $returnType instanceof NeverType && $returnType->isExplicit()) {
                $errors[] = \PHPStan\Rules\RuleErrorBuilder::message(sprintf($noDiscardReturnTypeMessage, $returnType->describe(VerbosityLevel::typeOnly())))->line($returnTypeNode->getStartLine())->identifier('attribute.target')->build();
            }
        }
        $returnTypeReferencedClasses = $this->getReturnTypeReferencedClasses($parametersAcceptor);
        foreach ($returnTypeReferencedClasses as $class) {
            if (!$this->reflectionProvider->hasClass($class)) {
                $errors[] = \PHPStan\Rules\RuleErrorBuilder::message(sprintf($returnMessage, $class))->line($returnTypeNode->getStartLine())->identifier('class.notFound')->build();
                continue;
            }
            if (!$this->reflectionProvider->getClass($class)->isTrait()) {
                continue;
            }
            $errors[] = \PHPStan\Rules\RuleErrorBuilder::message(sprintf($returnMessage, $class))->line($returnTypeNode->getStartLine())->identifier('return.trait')->build();
        }
        $locationData = [];
        if ($parametersAcceptor instanceof PhpMethodFromParserNodeReflection) {
            $locationData['method'] = $parametersAcceptor;
            if (!$parametersAcceptor->declaringClass->isAnonymous()) {
                $locationData['currentClassName'] = $parametersAcceptor->declaringClass->getName();
            }
        } else {
            $locationData['function'] = $parametersAcceptor;
        }
        $returnOriginalCasePairs = $this->getOriginalClassNamePairsFromTypeNode($functionNode->getReturnType());
        $errors = array_merge($errors, $this->classCheck->checkClassNames($scope, array_map(static function (string $class) use ($returnTypeNode, $returnOriginalCasePairs): \PHPStan\Rules\ClassNameNodePair {
            $lowerClass = strtolower($class);
            if (isset($returnOriginalCasePairs[$lowerClass])) {
                return $returnOriginalCasePairs[$lowerClass];
            }
            return new \PHPStan\Rules\ClassNameNodePair($class, $returnTypeNode);
        }, $returnTypeReferencedClasses), \PHPStan\Rules\ClassNameUsageLocation::from(\PHPStan\Rules\ClassNameUsageLocation::RETURN_TYPE, $locationData), $this->checkClassCaseSensitivity));
        if ($parametersAcceptor->getReturnType() instanceof NonexistentParentClassType) {
            $errors[] = \PHPStan\Rules\RuleErrorBuilder::message(sprintf($returnMessage, $parametersAcceptor->getReturnType()->describe(VerbosityLevel::typeOnly())))->line($returnTypeNode->getStartLine())->identifier('return.noParent')->build();
        }
        $templateTypeMap = $parametersAcceptor->getTemplateTypeMap();
        $templateTypes = $templateTypeMap->getTypes();
        if (count($templateTypes) > 0) {
            foreach ($parametersAcceptor->getParameters() as $parameter) {
                TypeTraverser::map($parameter->getType(), static function (Type $type, callable $traverse) use (&$templateTypes): Type {
                    if ($type instanceof TemplateType) {
                        unset($templateTypes[$type->getName()]);
                        return $traverse($type);
                    }
                    return $traverse($type);
                });
            }
            $returnType = $parametersAcceptor->getReturnType();
            if ($returnType instanceof ConditionalTypeForParameter && !$returnType->isNegated()) {
                TypeTraverser::map($returnType, static function (Type $type, callable $traverse) use (&$templateTypes): Type {
                    if ($type instanceof TemplateType) {
                        unset($templateTypes[$type->getName()]);
                        return $traverse($type);
                    }
                    return $traverse($type);
                });
            }
            foreach (array_keys($templateTypes) as $templateTypeName) {
                $errors[] = \PHPStan\Rules\RuleErrorBuilder::message(sprintf($templateTypeMissingInParameterMessage, $templateTypeName))->identifier('method.templateTypeNotInParameter')->build();
            }
        }
        return $errors;
    }
    /**
     * @param Param[] $parameterNodes
     * @return list<IdentifierRuleError>
     */
    private function checkRequiredParameterAfterOptional(array $parameterNodes): array
    {
        /** @var string|null $optionalParameter */
        $optionalParameter = null;
        $errors = [];
        $targetPhpVersion = null;
        foreach ($parameterNodes as $parameterNode) {
            if (!$parameterNode->var instanceof Variable) {
                throw new ShouldNotHappenException();
            }
            if (!is_string($parameterNode->var->name)) {
                throw new ShouldNotHappenException();
            }
            $parameterName = $parameterNode->var->name;
            if ($optionalParameter !== null && $parameterNode->default === null && !$parameterNode->variadic) {
                $errors[] = \PHPStan\Rules\RuleErrorBuilder::message(sprintf('Deprecated in PHP %s: Required parameter $%s follows optional parameter $%s.', $targetPhpVersion ?? '8.0', $parameterName, $optionalParameter))->line($parameterNode->getStartLine())->identifier('parameter.requiredAfterOptional')->build();
                $targetPhpVersion = null;
                continue;
            }
            if ($parameterNode->default === null) {
                continue;
            }
            if ($parameterNode->type === null) {
                $optionalParameter = $parameterName;
                continue;
            }
            $defaultValue = $parameterNode->default;
            if (!$defaultValue instanceof ConstFetch) {
                $optionalParameter = $parameterName;
                continue;
            }
            $constantName = strtolower($defaultValue->name->name);
            if ($constantName === 'null') {
                if (!($this->phpVersion->versionId >= 80100)) {
                    continue;
                }
                $parameterNodeType = $parameterNode->type;
                if ($parameterNodeType instanceof NullableType) {
                    $targetPhpVersion = '8.1';
                }
                if ($this->phpVersion->versionId >= 80300) {
                    $types = [];
                    if ($parameterNodeType instanceof UnionType) {
                        $types = $parameterNodeType->types;
                    } elseif ($parameterNodeType instanceof Identifier) {
                        $types = [$parameterNodeType];
                    }
                    $nullOrMixed = array_filter($types, static fn($type): bool => $type instanceof Identifier && in_array($type->name, ['null', 'mixed'], \true));
                    if (0 < count($nullOrMixed)) {
                        $targetPhpVersion = '8.3';
                    }
                }
                if ($targetPhpVersion === null) {
                    continue;
                }
            }
            $optionalParameter = $parameterName;
        }
        return $errors;
    }
    /**
     * @param Param[] $parameterNodes
     */
    private function getParameterNode(string $parameterName, array $parameterNodes): Param
    {
        foreach ($parameterNodes as $param) {
            if ($param->var instanceof Node\Expr\Error) {
                continue;
            }
            if (!is_string($param->var->name)) {
                continue;
            }
            if ($param->var->name === $parameterName) {
                return $param;
            }
        }
        throw new ShouldNotHappenException(sprintf('Parameter %s not found.', $parameterName));
    }
    /**
     * @return non-empty-string[]
     */
    private function getParameterReferencedClasses(ParameterReflection $parameter): array
    {
        if (!$parameter instanceof ExtendedParameterReflection) {
            return $parameter->getType()->getReferencedClasses();
        }
        if ($this->checkThisOnly) {
            return $parameter->getNativeType()->getReferencedClasses();
        }
        $moreClasses = [];
        if ($parameter->getOutType() !== null) {
            $moreClasses = array_merge($moreClasses, $parameter->getOutType()->getReferencedClasses());
        }
        if ($parameter->getClosureThisType() !== null) {
            $moreClasses = array_merge($moreClasses, $parameter->getClosureThisType()->getReferencedClasses());
        }
        return array_merge($parameter->getNativeType()->getReferencedClasses(), $parameter->getPhpDocType()->getReferencedClasses(), $moreClasses);
    }
    /**
     * @return non-empty-string[]
     */
    private function getReturnTypeReferencedClasses(ParametersAcceptor $parametersAcceptor): array
    {
        if (!$parametersAcceptor instanceof ExtendedParametersAcceptor) {
            return $parametersAcceptor->getReturnType()->getReferencedClasses();
        }
        if ($this->checkThisOnly) {
            return $parametersAcceptor->getNativeReturnType()->getReferencedClasses();
        }
        return array_merge($parametersAcceptor->getNativeReturnType()->getReferencedClasses(), $parametersAcceptor->getPhpDocReturnType()->getReferencedClasses());
    }
    /**
     * @param \PhpParser\Node\Identifier|\PhpParser\Node\Name|\PhpParser\Node\ComplexType|null $type
     */
    private function checkImplicitlyNullableType($type, ?Node\Expr $default, int $order, int $line, string $name): ?\PHPStan\Rules\IdentifierRuleError
    {
        if (!$default instanceof ConstFetch) {
            return null;
        }
        if (strtolower($default->name->name) !== 'null') {
            return null;
        }
        if ($type === null) {
            return null;
        }
        if ($type instanceof NullableType || $type instanceof IntersectionType) {
            return null;
        }
        if (!($this->phpVersion->versionId >= 80400)) {
            return null;
        }
        if ($type instanceof Identifier && strtolower($type->name) === 'mixed') {
            return null;
        }
        if ($type instanceof Identifier && strtolower($type->name) === 'null') {
            return null;
        }
        if ($type instanceof Name && strtolower($type->name) === 'null') {
            return null;
        }
        if ($type instanceof UnionType) {
            foreach ($type->types as $innerType) {
                if ($innerType instanceof Identifier && strtolower($innerType->name) === 'null') {
                    return null;
                }
            }
        }
        return \PHPStan\Rules\RuleErrorBuilder::message(sprintf('Deprecated in PHP 8.4: Parameter #%d $%s (%s) is implicitly nullable via default value null.', $order, $name, NodeTypePrinter::printType($type)))->line($line)->identifier('parameter.implicitlyNullable')->build();
    }
    /**
     * @return array<string, ClassNameNodePair>
     * @param \PhpParser\Node\Identifier|\PhpParser\Node\Name|\PhpParser\Node\ComplexType|null $typeNode
     */
    private function getOriginalClassNamePairsFromTypeNode($typeNode): array
    {
        if ($typeNode === null) {
            return [];
        }
        if ($typeNode instanceof Name) {
            $resolvedName = $typeNode->name;
            if ($this->checkImportedClassNameCase) {
                $nameAsWritten = \PHPStan\Rules\ClassCaseSensitivityCheck::getNameAsWritten($typeNode);
                if ($nameAsWritten === $resolvedName) {
                    return [];
                }
                return [strtolower($resolvedName) => new \PHPStan\Rules\ClassNameNodePair($nameAsWritten, $typeNode)];
            }
            $originalName = $typeNode->getAttribute('originalName');
            if (!$originalName instanceof Name) {
                return [];
            }
            $originalParts = \explode('\\', $originalName->name);
            $resolvedParts = \explode('\\', $typeNode->name);
            $originalPartsCount = count($originalParts);
            $resolvedPartsCount = count($resolvedParts);
            if ($originalPartsCount <= $resolvedPartsCount) {
                $prefixParts = array_slice($resolvedParts, 0, $resolvedPartsCount - $originalPartsCount);
                $originalCaseClassName = implode('\\', array_merge($prefixParts, $originalParts));
            } else {
                $originalCaseClassName = $originalName->name;
            }
            if ($originalCaseClassName === $resolvedName) {
                return [];
            }
            return [strtolower($resolvedName) => new \PHPStan\Rules\ClassNameNodePair($originalCaseClassName, $typeNode)];
        }
        if ($typeNode instanceof NullableType) {
            return $this->getOriginalClassNamePairsFromTypeNode($typeNode->type);
        }
        if ($typeNode instanceof UnionType || $typeNode instanceof IntersectionType) {
            $pairs = [];
            foreach ($typeNode->types as $innerType) {
                $pairs += $this->getOriginalClassNamePairsFromTypeNode($innerType);
            }
            return $pairs;
        }
        return [];
    }
}
