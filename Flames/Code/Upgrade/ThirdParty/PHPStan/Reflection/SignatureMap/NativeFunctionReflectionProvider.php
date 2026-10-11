<?php

declare (strict_types=1);
namespace PHPStan\Reflection\SignatureMap;

use PHPStan\BetterReflection\Identifier\Exception\InvalidIdentifierName;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\AttributeReflectionFactory;
use PHPStan\Reflection\ExtendedFunctionVariant;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\Native\ExtendedNativeParameterReflection;
use PHPStan\Reflection\Native\NativeFunctionReflection;
use PHPStan\Reflection\ParameterAllowedConstantsMapProvider;
use PHPStan\TrinaryLogic;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use function array_key_exists;
use function array_map;
use function str_contains;
use function strtolower;
#[AutowiredService]
final class NativeFunctionReflectionProvider
{
    private \PHPStan\Reflection\SignatureMap\SignatureMapProvider $signatureMapProvider;
    private Reflector $reflector;
    private FileTypeMapper $fileTypeMapper;
    private StubPhpDocProvider $stubPhpDocProvider;
    private AttributeReflectionFactory $attributeReflectionFactory;
    private ParameterAllowedConstantsMapProvider $allowedConstantsMapProvider;
    /** @var NativeFunctionReflection[] */
    private array $functionMap = [];
    public function __construct(
        \PHPStan\Reflection\SignatureMap\SignatureMapProvider $signatureMapProvider,
        #[AutowiredParameter('@betterReflectionReflector')]
        Reflector $reflector,
        FileTypeMapper $fileTypeMapper,
        StubPhpDocProvider $stubPhpDocProvider,
        AttributeReflectionFactory $attributeReflectionFactory,
        ParameterAllowedConstantsMapProvider $allowedConstantsMapProvider
    )
    {
        $this->signatureMapProvider = $signatureMapProvider;
        $this->reflector = $reflector;
        $this->fileTypeMapper = $fileTypeMapper;
        $this->stubPhpDocProvider = $stubPhpDocProvider;
        $this->attributeReflectionFactory = $attributeReflectionFactory;
        $this->allowedConstantsMapProvider = $allowedConstantsMapProvider;
    }
    public function findFunctionReflection(string $functionName): ?NativeFunctionReflection
    {
        $lowerCasedFunctionName = strtolower($functionName);
        $realFunctionName = $lowerCasedFunctionName;
        if (isset($this->functionMap[$lowerCasedFunctionName])) {
            return $this->functionMap[$lowerCasedFunctionName];
        }
        if (!$this->signatureMapProvider->hasFunctionSignature($lowerCasedFunctionName)) {
            return null;
        }
        $throwType = null;
        $reflectionFunctionAdapter = null;
        $isDeprecated = \false;
        $phpDocReturnType = null;
        $asserts = Assertions::createEmpty();
        $docComment = null;
        $returnsByReference = TrinaryLogic::createMaybe();
        $acceptsNamedArguments = \true;
        $fileName = null;
        $attributes = [];
        try {
            $reflectionFunction = $this->reflector->reflectFunction($functionName);
            $reflectionFunctionAdapter = new ReflectionFunction($reflectionFunction);
            $attributes = $reflectionFunctionAdapter->getAttributes();
            $returnsByReference = TrinaryLogic::createFromBoolean($reflectionFunctionAdapter->betterReflectionFunction->returnsReference());
            $realFunctionName = $reflectionFunction->getName();
            $isDeprecated = $reflectionFunction->isDeprecated();
            if ($reflectionFunction->getFileName() !== null) {
                $fileName = $reflectionFunction->getFileName();
                if (!$reflectionFunctionAdapter->betterReflectionFunction->isInternal() && !str_contains(strtolower($fileName), 'polyfill')) {
                    return null;
                }
                $docComment = $reflectionFunction->getDocComment();
                if ($docComment !== null) {
                    $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc($fileName, null, null, $reflectionFunction->getName(), $docComment);
                    $throwsTag = $resolvedPhpDoc->getThrowsTag();
                    if ($throwsTag !== null) {
                        $throwType = $throwsTag->type;
                    }
                }
            }
        } catch (IdentifierNotFound|InvalidIdentifierName $e) {
            // pass
        }
        $functionSignaturesResult = $this->signatureMapProvider->getFunctionSignatures($lowerCasedFunctionName, null, $reflectionFunctionAdapter);
        $phpDoc = $this->stubPhpDocProvider->findFunctionPhpDoc($lowerCasedFunctionName, array_map(static fn(\PHPStan\Reflection\SignatureMap\ParameterSignature $parameter): string => $parameter->name, $functionSignaturesResult['positional'][0]->parameters));
        if ($phpDoc !== null) {
            if ($phpDoc->hasPhpDocString()) {
                $docComment = $phpDoc->phpDocString;
            }
            if ($phpDoc->getThrowsTag() !== null) {
                $throwType = $phpDoc->getThrowsTag()->type;
            }
            $asserts = Assertions::createFromResolvedPhpDocBlock($phpDoc);
            $phpDocReturnType = $this->getReturnTypeFromPhpDoc($phpDoc);
            $acceptsNamedArguments = $phpDoc->acceptsNamedArguments();
        }
        $allowedConstantsMapProvider = $this->allowedConstantsMapProvider;
        $pureUnlessCallableIsImpureParameters = [];
        $pureUnlessParameterPassedParameters = [];
        if ($this->signatureMapProvider->hasFunctionMetadata($lowerCasedFunctionName)) {
            $functionMetadata = $this->signatureMapProvider->getFunctionMetadata($lowerCasedFunctionName);
            if (isset($functionMetadata['pureUnlessCallableIsImpureParameters'])) {
                $pureUnlessCallableIsImpureParameters = $functionMetadata['pureUnlessCallableIsImpureParameters'];
            }
            if (isset($functionMetadata['pureUnlessParameterPassedParameters'])) {
                $pureUnlessParameterPassedParameters = $functionMetadata['pureUnlessParameterPassedParameters'];
            }
        } else {
            $functionMetadata = null;
        }
        $variantsByType = ['positional' => []];
        foreach ($functionSignaturesResult as $signatureType => $functionSignatures) {
            foreach ($functionSignatures ?? [] as $functionSignature) {
                $variantsByType[$signatureType][] = new ExtendedFunctionVariant(TemplateTypeMap::createEmpty(), null, array_map(static function (\PHPStan\Reflection\SignatureMap\ParameterSignature $parameterSignature) use ($phpDoc, $lowerCasedFunctionName, $allowedConstantsMapProvider, $pureUnlessCallableIsImpureParameters, $pureUnlessParameterPassedParameters): ExtendedNativeParameterReflection {
                    $name = $parameterSignature->name;
                    $type = $parameterSignature->type;
                    $phpDocType = null;
                    $immediatelyInvokedCallable = TrinaryLogic::createMaybe();
                    $closureThisType = null;
                    $pureUnlessCallableIsImpureParameter = TrinaryLogic::createFromBoolean($pureUnlessCallableIsImpureParameters[$name] ?? \false);
                    $pureUnlessParameterPassedParameter = TrinaryLogic::createFromBoolean($pureUnlessParameterPassedParameters[$name] ?? \false);
                    if ($phpDoc !== null) {
                        if (array_key_exists($parameterSignature->name, $phpDoc->getParamTags())) {
                            $phpDocType = $phpDoc->getParamTags()[$parameterSignature->getName()]->type;
                        }
                        if (array_key_exists($parameterSignature->name, $phpDoc->getParamsImmediatelyInvokedCallable())) {
                            $immediatelyInvokedCallable = TrinaryLogic::createFromBoolean($phpDoc->getParamsImmediatelyInvokedCallable()[$parameterSignature->name]);
                        }
                        if (array_key_exists($parameterSignature->name, $phpDoc->getParamClosureThisTags())) {
                            $closureThisType = $phpDoc->getParamClosureThisTags()[$parameterSignature->getName()]->type;
                        }
                        if (($phpDoc->getParamsPureUnlessCallableIsImpure()[$parameterSignature->name] ?? \false) === \true) {
                            $pureUnlessCallableIsImpureParameter = TrinaryLogic::createYes();
                        }
                        if (($phpDoc->getParamsPureUnlessParameterPassed()[$parameterSignature->name] ?? \false) === \true) {
                            $pureUnlessParameterPassedParameter = TrinaryLogic::createYes();
                        }
                    }
                    return new ExtendedNativeParameterReflection($parameterSignature->name, $parameterSignature->optional, TypehintHelper::decideType($type, $phpDocType), $phpDocType ?? new MixedType(), $type, $parameterSignature->passedByReference, $parameterSignature->variadic, $parameterSignature->defaultValue, $phpDoc !== null ? \PHPStan\Reflection\SignatureMap\NativeFunctionReflectionProvider::getParamOutTypeFromPhpDoc($parameterSignature->name, $phpDoc) : null, $immediatelyInvokedCallable, $closureThisType, [], $allowedConstantsMapProvider->getForFunctionParameter($lowerCasedFunctionName, $parameterSignature->name), $pureUnlessCallableIsImpureParameter, $pureUnlessParameterPassedParameter);
                }, $functionSignature->parameters), $functionSignature->variadic, TypehintHelper::decideType($functionSignature->returnType, $phpDocReturnType), $phpDocReturnType ?? new MixedType(), $functionSignature->returnType);
            }
        }
        $hasSideEffects = isset($functionMetadata['hasSideEffects']) ? TrinaryLogic::createFromBoolean($functionMetadata['hasSideEffects']) : TrinaryLogic::createMaybe();
        $functionReflection = new NativeFunctionReflection($realFunctionName, $variantsByType['positional'], $variantsByType['named'] ?? null, $throwType, $hasSideEffects, $isDeprecated, $asserts, $docComment, $returnsByReference, $acceptsNamedArguments, $this->attributeReflectionFactory->fromNativeReflection($attributes, InitializerExprContext::fromFunction($realFunctionName, $fileName)));
        $this->functionMap[$lowerCasedFunctionName] = $functionReflection;
        return $functionReflection;
    }
    private function getReturnTypeFromPhpDoc(ResolvedPhpDocBlock $phpDoc): ?Type
    {
        $returnTag = $phpDoc->getReturnTag();
        if ($returnTag === null) {
            return null;
        }
        return $returnTag->type;
    }
    private static function getParamOutTypeFromPhpDoc(string $paramName, ResolvedPhpDocBlock $stubPhpDoc): ?Type
    {
        $paramOutTags = $stubPhpDoc->getParamOutTags();
        if (array_key_exists($paramName, $paramOutTags)) {
            return $paramOutTags[$paramName]->type;
        }
        return null;
    }
}
