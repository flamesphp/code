<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\TypeInferer\ReturnTypeInferer;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\VoidType;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\NodeTypeResolver\PHPStan\Type\TypeFactory;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\Reflection\ReflectionResolver;
use Flames\Code\Upgrade\Rules\TypeDeclaration\TypeInferer\SilentVoidResolver;
use Flames\Code\Upgrade\Rules\TypeDeclaration\TypeInferer\SplArrayFixedTypeNarrower;
/**
 * @internal
 */
final readonly class ReturnedNodesReturnTypeInfererTypeInferer
{
    public function __construct(private SilentVoidResolver $silentVoidResolver, private BetterNodeFinder $betterNodeFinder, private NodeTypeResolver $nodeTypeResolver, private TypeFactory $typeFactory, private SplArrayFixedTypeNarrower $splArrayFixedTypeNarrower, private ReflectionResolver $reflectionResolver)
    {
    }
    /**
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_|\PhpParser\Node\Expr\Closure $functionLike
     */
    public function inferFunctionLike($functionLike): Type
    {
        $classReflection = $this->reflectionResolver->resolveClassReflection($functionLike);
        if ($functionLike instanceof ClassMethod && (!$classReflection instanceof ClassReflection || $classReflection->isInterface())) {
            return new MixedType();
        }
        $types = [];
        // empty returns can have yield, use MixedType() instead
        $localReturnNodes = $this->betterNodeFinder->findReturnsScoped($functionLike);
        if ($localReturnNodes === []) {
            return new MixedType();
        }
        $hasVoid = \false;
        foreach ($localReturnNodes as $localReturnNode) {
            if (!$localReturnNode->expr instanceof Expr) {
                $hasVoid = \true;
                $types[] = new VoidType();
                continue;
            }
            $returnedExprType = $this->nodeTypeResolver->getNativeType($localReturnNode->expr);
            $types[] = $this->splArrayFixedTypeNarrower->narrow($returnedExprType);
        }
        if (!$hasVoid && $this->silentVoidResolver->hasSilentVoid($functionLike)) {
            $types[] = new VoidType();
        }
        $returnType = $this->typeFactory->createMixedPassedOrUnionTypeAndKeepConstant($types);
        // only void?
        if ($returnType->isVoid()->yes()) {
            return new MixedType();
        }
        return $returnType;
    }
}
