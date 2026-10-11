<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\TypeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ClassConstFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\NullableType;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Float_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\Int_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar\String_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\Native\NativeFunctionReflection;
use PHPStan\Reflection\Native\NativeMethodReflection;
use PHPStan\Type\MixedType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\NodeTypeResolver\PHPStan\ParametersAcceptorSelectorVariantsWrapper;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
use Flames\Code\Upgrade\Rules\TypeDeclaration\NodeAnalyzer\TypeNodeUnwrapper;
final readonly class ReturnStrictTypeAnalyzer
{
    public function __construct(private ReflectionResolver $reflectionResolver, private TypeNodeUnwrapper $typeNodeUnwrapper, private StaticTypeMapper $staticTypeMapper)
    {
    }
    /**
     * @param Return_[] $returns
     * @return array<Identifier|Name|NullableType>
     */
    public function collectStrictReturnTypes(array $returns, Scope $scope): array
    {
        $containsStrictCall = \false;
        $returnedStrictTypeNodes = [];
        foreach ($returns as $return) {
            if (!$return->expr instanceof Expr) {
                return [];
            }
            $returnedExpr = $return->expr;
            if ($returnedExpr instanceof MethodCall || $returnedExpr instanceof StaticCall || $returnedExpr instanceof FuncCall) {
                $containsStrictCall = \true;
                $returnNode = $this->resolveMethodCallReturnNode($returnedExpr);
            } elseif ($returnedExpr instanceof ClassConstFetch) {
                $returnNode = $this->resolveConstFetchReturnNode($returnedExpr, $scope);
            } elseif ($returnedExpr instanceof Array_ || $returnedExpr instanceof String_ || $returnedExpr instanceof Int_ || $returnedExpr instanceof Float_) {
                $returnNode = $this->resolveLiteralReturnNode($returnedExpr, $scope);
            } else {
                return [];
            }
            if (!$returnNode instanceof Node) {
                return [];
            }
            if ($returnNode instanceof Identifier && $returnNode->toString() === 'void') {
                return [];
            }
            $returnedStrictTypeNodes[] = $returnNode;
        }
        if (!$containsStrictCall) {
            return [];
        }
        return $this->typeNodeUnwrapper->uniquateNodes($returnedStrictTypeNodes);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall $call
     */
    public function resolveMethodCallReturnNode($call): ?Node
    {
        $returnType = $this->resolveMethodCallReturnType($call);
        if (!$returnType instanceof Type) {
            return null;
        }
        return $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($returnType, TypeKind::RETURN);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall $call
     */
    public function resolveMethodCallReturnType($call): ?Type
    {
        $methodReflection = $this->reflectionResolver->resolveFunctionLikeReflectionFromCall($call);
        if ($methodReflection === null) {
            return null;
        }
        $scope = $call->getAttribute(AttributeKey::SCOPE);
        if (!$scope instanceof Scope) {
            return null;
        }
        $parametersAcceptorWithPhpDocs = ParametersAcceptorSelectorVariantsWrapper::select($methodReflection, $call, $scope);
        if ($methodReflection instanceof NativeFunctionReflection || $methodReflection instanceof NativeMethodReflection) {
            $returnType = $parametersAcceptorWithPhpDocs->getReturnType();
        } elseif ($parametersAcceptorWithPhpDocs instanceof ExtendedParametersAcceptor) {
            // native return type is needed, as docblock can be false
            $returnType = $parametersAcceptorWithPhpDocs->getNativeReturnType();
        } else {
            $returnType = $parametersAcceptorWithPhpDocs->getReturnType();
        }
        if ($returnType instanceof MixedType) {
            if ($returnType->isExplicitMixed()) {
                return $returnType;
            }
            return null;
        }
        return $this->normalizeStaticType($call, $returnType);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\MethodCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\StaticCall|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall $call
     */
    private function normalizeStaticType($call, Type $type): Type
    {
        $reflectionClass = $this->reflectionResolver->resolveClassReflection($call);
        $currentClassName = $reflectionClass instanceof ClassReflection ? $reflectionClass->getName() : null;
        return TypeTraverser::map($type, static function (Type $currentType, callable $traverseCallback) use ($currentClassName): Type {
            if ($currentType instanceof StaticType && $currentClassName !== $currentType->getClassName()) {
                return new FullyQualifiedObjectType($currentType->getClassName());
            }
            return $traverseCallback($currentType);
        });
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Array_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Scalar $returnedExpr
     */
    private function resolveLiteralReturnNode($returnedExpr, Scope $scope): ?Node
    {
        $returnType = $scope->getType($returnedExpr);
        return $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($returnType, TypeKind::RETURN);
    }
    private function resolveConstFetchReturnNode(ClassConstFetch $classConstFetch, Scope $scope): ?Node
    {
        $constType = $scope->getType($classConstFetch);
        if ($constType instanceof MixedType) {
            return null;
        }
        return $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($constType, TypeKind::RETURN);
    }
}
