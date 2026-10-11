<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\TypeResolver;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Yield_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\YieldFrom;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\NodeTypeResolver\PHPStan\Type\TypeFactory;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\FullyQualifiedGenericObjectType;
use Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
final readonly class YieldTypeResolver
{
    public function __construct(private NodeTypeResolver $nodeTypeResolver, private NodeNameResolver $nodeNameResolver, private TypeFactory $typeFactory)
    {
    }
    /**
     * @param array<Yield_|YieldFrom> $yieldNodes
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_ $functionLike
     * @return \Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType|\Flames\Code\Upgrade\StaticTypeMapper\ValueObject\Type\FullyQualifiedGenericObjectType
     */
    public function resolveFromYieldNodes(array $yieldNodes, $functionLike)
    {
        $yieldedTypes = $this->resolveYieldedTypes($yieldNodes);
        $className = $this->resolveClassName($functionLike);
        if ($yieldedTypes === []) {
            return new FullyQualifiedObjectType($className);
        }
        $yieldedTypes = $this->typeFactory->createMixedPassedOrUnionType($yieldedTypes);
        return new FullyQualifiedGenericObjectType($className, [$yieldedTypes]);
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Yield_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\YieldFrom $yield
     */
    private function resolveYieldValue($yield): ?Expr
    {
        if ($yield instanceof Yield_) {
            return $yield->value;
        }
        return $yield->expr;
    }
    /**
     * @param array<Yield_|YieldFrom> $yieldNodes
     * @return Type[]
     */
    private function resolveYieldedTypes(array $yieldNodes): array
    {
        $yieldedTypes = [];
        foreach ($yieldNodes as $yieldNode) {
            $value = $this->resolveYieldValue($yieldNode);
            if (!$value instanceof Expr) {
                // one of the yields is empty
                return [];
            }
            $resolvedType = $this->nodeTypeResolver->getType($value);
            if ($resolvedType instanceof MixedType) {
                continue;
            }
            $yieldedTypes[] = $resolvedType;
        }
        return $yieldedTypes;
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Function_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure $functionLike
     */
    private function resolveClassName($functionLike): string
    {
        $returnTypeNode = $functionLike->getReturnType();
        if ($returnTypeNode instanceof Identifier && $returnTypeNode->name === 'iterable') {
            return 'Iterator';
        }
        if ($returnTypeNode instanceof Name && !$this->nodeNameResolver->isName($returnTypeNode, 'Generator')) {
            return $this->nodeNameResolver->getName($returnTypeNode);
        }
        return 'Generator';
    }
}
