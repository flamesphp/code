<?php

declare (strict_types=1);
namespace PHPStan\Reflection\SignatureMap;

use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Php8StubsMap;
use PHPStan\PhpDoc\Tag\ParamTag;
use PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\MixedType;
use PHPStan\Type\ParserNodeTypeToPHPStanType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypehintHelper;
use ReflectionFunctionAbstract;
use function array_key_exists;
use function array_map;
use function count;
use function explode;
use function is_string;
use function sprintf;
use function strtolower;
#[AutowiredService(\null, \null, \PHPStan\Reflection\SignatureMap\Php8SignatureMapProvider::class)]
final class Php8SignatureMapProvider implements \PHPStan\Reflection\SignatureMap\SignatureMapProvider
{
    private \PHPStan\Reflection\SignatureMap\FunctionSignatureMapProvider $functionSignatureMapProvider;
    private FileNodesFetcher $fileNodesFetcher;
    private FileTypeMapper $fileTypeMapper;
    private PhpVersion $phpVersion;
    private InitializerExprTypeResolver $initializerExprTypeResolver;
    private ReflectionProviderProvider $reflectionProviderProvider;
    private const DIRECTORY = __DIR__ . '/../../Php8Stubs';
    /** @var array<lowercase-string, array<lowercase-string, array{ClassMethod, string}>> */
    private array $methodNodes = [];
    /** @var array<lowercase-string, array<lowercase-string, Type|null>> */
    private array $constantTypes = [];
    private Php8StubsMap $map;
    public function __construct(\PHPStan\Reflection\SignatureMap\FunctionSignatureMapProvider $functionSignatureMapProvider, FileNodesFetcher $fileNodesFetcher, FileTypeMapper $fileTypeMapper, PhpVersion $phpVersion, InitializerExprTypeResolver $initializerExprTypeResolver, ReflectionProviderProvider $reflectionProviderProvider)
    {
        $this->functionSignatureMapProvider = $functionSignatureMapProvider;
        $this->fileNodesFetcher = $fileNodesFetcher;
        $this->fileTypeMapper = $fileTypeMapper;
        $this->phpVersion = $phpVersion;
        $this->initializerExprTypeResolver = $initializerExprTypeResolver;
        $this->reflectionProviderProvider = $reflectionProviderProvider;
        $this->map = new Php8StubsMap($phpVersion->versionId);
    }
    public function hasMethodSignature(string $className, string $methodName): bool
    {
        $lowerClassName = strtolower($className);
        if (!array_key_exists($lowerClassName, $this->map->classes)) {
            return $this->functionSignatureMapProvider->hasFunctionSignature(\sprintf('%s::%s', $className, $methodName));
        }
        if ($this->findMethodNode($className, $methodName) === null) {
            return $this->functionSignatureMapProvider->hasFunctionSignature(\sprintf('%s::%s', $className, $methodName));
        }
        return \true;
    }
    /**
     * @return array{ClassMethod, string}|null
     */
    private function findMethodNode(string $className, string $methodName): ?array
    {
        $lowerClassName = strtolower($className);
        $lowerMethodName = strtolower($methodName);
        $this->findClassStubs($className);
        if (isset($this->methodNodes[$lowerClassName][$lowerMethodName])) {
            return $this->methodNodes[$lowerClassName][$lowerMethodName];
        }
        return null;
    }
    private function findClassStubs(string $className): void
    {
        $lowerClassName = strtolower($className);
        if (isset($this->methodNodes[$lowerClassName]) || isset($this->constantTypes[$lowerClassName])) {
            return;
        }
        $stubFile = self::DIRECTORY . '/' . $this->map->classes[$lowerClassName];
        $nodes = $this->fileNodesFetcher->fetchNodes($stubFile);
        $classes = $nodes->classNodes;
        if (count($classes) !== 1) {
            throw new ShouldNotHappenException(sprintf('Class %s stub not found in %s.', $className, $stubFile));
        }
        $class = $classes[$lowerClassName];
        if (count($class) !== 1) {
            throw new ShouldNotHappenException(sprintf('Class %s stub not found in %s.', $className, $stubFile));
        }
        $this->methodNodes[$lowerClassName] = [];
        $this->constantTypes[$lowerClassName] = [];
        // find and remember all methods/constants within the stubFile
        foreach ($class[0]->node->stmts as $stmt) {
            if ($stmt instanceof ClassMethod) {
                if (!$this->isForCurrentVersion($stmt->attrGroups)) {
                    continue;
                }
                $this->methodNodes[$lowerClassName][strtolower($stmt->name->name)] = [$stmt, $stubFile];
                continue;
            }
            if (!$stmt instanceof ClassConst) {
                continue;
            }
            foreach ($stmt->consts as $const) {
                if ($stmt->type === null) {
                    continue;
                }
                if (!$this->isForCurrentVersion($stmt->attrGroups)) {
                    continue;
                }
                $this->constantTypes[$lowerClassName][strtolower($const->name->name)] = ParserNodeTypeToPHPStanType::resolve($stmt->type, null);
            }
        }
    }
    /**
     * @param AttributeGroup[] $attrGroups
     */
    private function isForCurrentVersion(array $attrGroups): bool
    {
        foreach ($attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if ($attr->name->name === 'Until') {
                    $arg = $attr->args[0]->value;
                    if (!$arg instanceof String_) {
                        throw new ShouldNotHappenException();
                    }
                    $parts = explode('.', $arg->value);
                    $versionId = (int) $parts[0] * 10000 + (int) ($parts[1] ?? 0) * 100 + (int) ($parts[2] ?? 0);
                    if ($this->phpVersion->versionId >= $versionId) {
                        return \false;
                    }
                }
                if ($attr->name->name !== 'Since') {
                    continue;
                }
                $arg = $attr->args[0]->value;
                if (!$arg instanceof String_) {
                    throw new ShouldNotHappenException();
                }
                $parts = explode('.', $arg->value);
                $versionId = (int) $parts[0] * 10000 + (int) ($parts[1] ?? 0) * 100 + (int) ($parts[2] ?? 0);
                if ($this->phpVersion->versionId < $versionId) {
                    return \false;
                }
            }
        }
        return \true;
    }
    public function hasFunctionSignature(string $name): bool
    {
        $lowerName = strtolower($name);
        if (!array_key_exists($lowerName, $this->map->functions)) {
            return $this->functionSignatureMapProvider->hasFunctionSignature($name);
        }
        return \true;
    }
    public function getMethodSignatures(string $className, string $methodName, ?ReflectionMethod $reflectionMethod): array
    {
        $lowerClassName = strtolower($className);
        if (!array_key_exists($lowerClassName, $this->map->classes)) {
            return $this->functionSignatureMapProvider->getFunctionSignatures(\sprintf('%s::%s', $className, $methodName), $className, $reflectionMethod);
        }
        $methodNode = $this->findMethodNode($className, $methodName);
        if ($methodNode === null) {
            return $this->functionSignatureMapProvider->getFunctionSignatures(\sprintf('%s::%s', $className, $methodName), $className, $reflectionMethod);
        }
        [$methodNode, $stubFile] = $methodNode;
        $signature = $this->getSignature($methodNode, $className, $stubFile);
        if ($this->functionSignatureMapProvider->hasFunctionSignature(\sprintf('%s::%s', $className, $methodName))) {
            $functionMapSignatures = $this->functionSignatureMapProvider->getFunctionSignatures(\sprintf('%s::%s', $className, $methodName), $className, $reflectionMethod);
            return $this->getMergedSignatures($signature, $functionMapSignatures);
        }
        return ['positional' => [$signature], 'named' => null];
    }
    /**
     * @param \ReflectionFunctionAbstract|null $reflectionFunction
     */
    public function getFunctionSignatures(string $functionName, ?string $className, $reflectionFunction): array
    {
        $lowerName = strtolower($functionName);
        if (!array_key_exists($lowerName, $this->map->functions)) {
            return $this->functionSignatureMapProvider->getFunctionSignatures($functionName, $className, $reflectionFunction);
        }
        $stubFile = self::DIRECTORY . '/' . $this->map->functions[$lowerName];
        $nodes = $this->fileNodesFetcher->fetchNodes($stubFile);
        $functions = $nodes->functionNodes;
        if (!array_key_exists($lowerName, $functions)) {
            throw new ShouldNotHappenException(sprintf('Function %s stub not found in %s.', $functionName, $stubFile));
        }
        foreach ($functions[$lowerName] as $functionNode) {
            if (!$this->isForCurrentVersion($functionNode->getNode()->attrGroups)) {
                continue;
            }
            $signature = $this->getSignature($functionNode->node, null, $stubFile);
            if ($this->functionSignatureMapProvider->hasFunctionSignature($functionName)) {
                $functionMapSignatures = $this->functionSignatureMapProvider->getFunctionSignatures($functionName, $className, $reflectionFunction);
                return $this->getMergedSignatures($signature, $functionMapSignatures);
            }
            return ['positional' => [$signature], 'named' => null];
        }
        throw new ShouldNotHappenException(sprintf('Function %s stub not found in %s.', $functionName, $stubFile));
    }
    /**
     * @param array{positional: array<int, FunctionSignature>, named: ?array<int, FunctionSignature>} $functionMapSignatures
     * @return array{positional: array<int, FunctionSignature>, named: ?array<int, FunctionSignature>}
     */
    private function getMergedSignatures(\PHPStan\Reflection\SignatureMap\FunctionSignature $nativeSignature, array $functionMapSignatures): array
    {
        if (count($functionMapSignatures['positional']) === 1) {
            return ['positional' => [$this->mergeSignatures($nativeSignature, $functionMapSignatures['positional'][0])], 'named' => null];
        }
        if (count($functionMapSignatures['positional']) === 0) {
            return ['positional' => [], 'named' => null];
        }
        $nativeParams = $nativeSignature->parameters;
        $namedArgumentsVariants = [];
        $allParamNamesMatchNative = \true;
        foreach ($functionMapSignatures['positional'] as $functionMapSignature) {
            $isPrevParamVariadic = \false;
            $hasMiddleVariadicParam = \false;
            // avoid weird functions like array_diff_uassoc
            foreach ($functionMapSignature->parameters as $i => $functionParam) {
                $nativeParam = $nativeParams[$i] ?? null;
                $allParamNamesMatchNative = $allParamNamesMatchNative && $nativeParam !== null && $functionParam->name === $nativeParam->name;
                $hasMiddleVariadicParam = $hasMiddleVariadicParam || $isPrevParamVariadic;
                $isPrevParamVariadic = $functionParam->variadic || ($nativeParam !== null ? $nativeParam->variadic : \false);
            }
            if ($hasMiddleVariadicParam) {
                continue;
            }
            $parameters = [];
            foreach ($functionMapSignature->parameters as $i => $functionParam) {
                if (!array_key_exists($i, $nativeParams)) {
                    continue 2;
                }
                // it seems that variadic parameters cannot be named in native functions/methods.
                $nativeParam = $nativeParams[$i];
                if ($nativeParam->variadic) {
                    break;
                }
                $parameters[] = new \PHPStan\Reflection\SignatureMap\ParameterSignature($nativeParam->name, $functionParam->optional, $functionParam->type, $functionParam->nativeType, $functionParam->passedByReference, $functionParam->variadic, $functionParam->defaultValue ?? $nativeParam->defaultValue, $functionParam->outType ?? $nativeParam->outType);
            }
            $namedArgumentsVariants[] = new \PHPStan\Reflection\SignatureMap\FunctionSignature($parameters, $functionMapSignature->returnType, $functionMapSignature->nativeReturnType, $functionMapSignature->variadic);
        }
        if ($allParamNamesMatchNative || count($namedArgumentsVariants) === 0) {
            $namedArgumentsVariants = null;
        }
        return ['positional' => $functionMapSignatures['positional'], 'named' => $namedArgumentsVariants];
    }
    private function mergeSignatures(\PHPStan\Reflection\SignatureMap\FunctionSignature $nativeSignature, \PHPStan\Reflection\SignatureMap\FunctionSignature $functionMapSignature): \PHPStan\Reflection\SignatureMap\FunctionSignature
    {
        $parameters = [];
        foreach ($nativeSignature->parameters as $i => $nativeParameter) {
            if (!array_key_exists($i, $functionMapSignature->parameters)) {
                $parameters[] = $nativeParameter;
                continue;
            }
            $functionMapParameter = $functionMapSignature->parameters[$i];
            $nativeParameterType = $nativeParameter->nativeType;
            $parameters[] = new \PHPStan\Reflection\SignatureMap\ParameterSignature($nativeParameter->name, $nativeParameter->optional, TypehintHelper::decideType($nativeParameterType, TypehintHelper::decideType($nativeParameter->type, $functionMapParameter->type)), $nativeParameterType, $nativeParameter->passedByReference->yes() ? $functionMapParameter->passedByReference : $nativeParameter->passedByReference, $nativeParameter->variadic, $nativeParameter->defaultValue, $nativeParameter->outType);
        }
        $nativeReturnType = $nativeSignature->nativeReturnType;
        if ($nativeReturnType instanceof MixedType && !$nativeReturnType->isExplicitMixed()) {
            $returnType = $functionMapSignature->returnType;
        } else {
            $returnType = TypehintHelper::decideType($nativeReturnType, TypehintHelper::decideType($nativeSignature->returnType, $functionMapSignature->returnType));
        }
        return new \PHPStan\Reflection\SignatureMap\FunctionSignature($parameters, $returnType, $nativeReturnType, $nativeSignature->variadic);
    }
    public function hasMethodMetadata(string $className, string $methodName): bool
    {
        return $this->functionSignatureMapProvider->hasFunctionMetadata(\sprintf('%s::%s', $className, $methodName));
    }
    public function hasFunctionMetadata(string $name): bool
    {
        return $this->functionSignatureMapProvider->hasFunctionMetadata($name);
    }
    /**
     * @return array{hasSideEffects: bool}
     */
    public function getMethodMetadata(string $className, string $methodName): array
    {
        return $this->functionSignatureMapProvider->getFunctionMetadata(\sprintf('%s::%s', $className, $methodName));
    }
    /**
     * @return array{hasSideEffects: bool}
     */
    public function getFunctionMetadata(string $functionName): array
    {
        return $this->functionSignatureMapProvider->getFunctionMetadata($functionName);
    }
    /**
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_ $function
     */
    private function getSignature($function, ?string $className, string $stubFile): \PHPStan\Reflection\SignatureMap\FunctionSignature
    {
        $phpDocParameterTypes = null;
        $phpDocReturnType = null;
        if ($function->getDocComment() !== null) {
            if ($function instanceof ClassMethod) {
                $functionName = $function->name->name;
            } elseif ($function->namespacedName !== null) {
                $functionName = $function->namespacedName->name;
            } else {
                throw new ShouldNotHappenException();
            }
            $phpDoc = $this->fileTypeMapper->getResolvedPhpDoc($stubFile, $className, null, $functionName, $function->getDocComment()->getText());
            $phpDocParameterTypes = array_map(static fn(ParamTag $param): Type => $param->type, $phpDoc->getParamTags());
            if ($phpDoc->getReturnTag() !== null) {
                $phpDocReturnType = $phpDoc->getReturnTag()->type;
            }
        }
        $classReflection = null;
        if ($className !== null) {
            $reflectionProvider = $this->reflectionProviderProvider->getReflectionProvider();
            $classReflection = $reflectionProvider->getClass($className);
        }
        $parameters = [];
        $variadic = \false;
        foreach ($function->getParams() as $param) {
            $name = $param->var;
            if (!$name instanceof Variable || !is_string($name->name)) {
                throw new ShouldNotHappenException();
            }
            $parameterType = ParserNodeTypeToPHPStanType::resolve($param->type, $classReflection);
            $phpDocParameterType = $phpDocParameterTypes[$name->name] ?? null;
            if ($param->default instanceof ConstFetch) {
                $constName = (string) $param->default->name;
                $loweredConstName = strtolower($constName);
                if ($loweredConstName === 'null') {
                    $parameterType = TypeCombinator::addNull($parameterType);
                    if ($phpDocParameterType !== null) {
                        $phpDocParameterType = TypeCombinator::addNull($phpDocParameterType);
                    }
                }
            }
            $parameters[] = new \PHPStan\Reflection\SignatureMap\ParameterSignature($name->name, $param->default !== null || $param->variadic, TypehintHelper::decideType($parameterType, $phpDocParameterType), $parameterType, $param->byRef ? PassedByReference::createCreatesNewVariable() : PassedByReference::createNo(), $param->variadic, $param->default !== null ? $this->initializerExprTypeResolver->getType($param->default, InitializerExprContext::fromStubParameter($className, $stubFile, $function)) : null, null);
            $variadic = $variadic || $param->variadic;
        }
        $returnType = ParserNodeTypeToPHPStanType::resolve($function->getReturnType(), $classReflection);
        return new \PHPStan\Reflection\SignatureMap\FunctionSignature($parameters, TypehintHelper::decideType($returnType, $phpDocReturnType), $returnType, $variadic);
    }
    public function hasClassConstantMetadata(string $className, string $constantName): bool
    {
        $lowerClassName = strtolower($className);
        if (!array_key_exists($lowerClassName, $this->map->classes)) {
            return \false;
        }
        return $this->findConstantType($className, $constantName) !== null;
    }
    public function getClassConstantMetadata(string $className, string $constantName): array
    {
        $lowerClassName = strtolower($className);
        if (!array_key_exists($lowerClassName, $this->map->classes)) {
            throw new ShouldNotHappenException();
        }
        $type = $this->findConstantType($className, $constantName);
        if ($type === null) {
            throw new ShouldNotHappenException();
        }
        return ['nativeType' => $type];
    }
    private function findConstantType(string $className, string $constantName): ?Type
    {
        $lowerClassName = strtolower($className);
        $lowerConstantName = strtolower($constantName);
        $this->findClassStubs($className);
        if (isset($this->constantTypes[$lowerClassName][$lowerConstantName])) {
            return $this->constantTypes[$lowerClassName][$lowerConstantName];
        }
        return null;
    }
}
