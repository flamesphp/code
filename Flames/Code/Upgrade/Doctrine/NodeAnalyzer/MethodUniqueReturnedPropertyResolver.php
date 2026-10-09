<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Doctrine\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Property;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
final readonly class MethodUniqueReturnedPropertyResolver
{
    public function __construct(private BetterNodeFinder $betterNodeFinder, private NodeNameResolver $nodeNameResolver)
    {
    }
    public function resolve(Class_ $class, ClassMethod $classMethod): ?Property
    {
        $returns = $this->betterNodeFinder->findInstancesOfInFunctionLikeScoped($classMethod, Return_::class);
        if (\count($returns) !== 1) {
            return null;
        }
        $return = \reset($returns);
        $returnExpr = $return->expr;
        if (!$returnExpr instanceof PropertyFetch) {
            return null;
        }
        $propertyName = (string) $this->nodeNameResolver->getName($returnExpr);
        return $class->getProperty($propertyName);
    }
}
