<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\ValueObject\MethodName;
final readonly class ConstructorAssignedTypeResolver
{
    public function __construct(private BetterNodeFinder $betterNodeFinder, private NodeNameResolver $nodeNameResolver, private NodeTypeResolver $nodeTypeResolver)
    {
    }
    public function resolve(Class_ $class, string $propertyName): ?Type
    {
        $constructorClassMethod = $class->getMethod(MethodName::CONSTRUCT);
        if (!$constructorClassMethod instanceof ClassMethod) {
            return null;
        }
        if ($constructorClassMethod->stmts === null) {
            return null;
        }
        $assigns = $this->betterNodeFinder->findInstancesOfScoped($constructorClassMethod->stmts, Assign::class);
        foreach ($assigns as $assign) {
            if (!$assign->var instanceof PropertyFetch) {
                continue;
            }
            $propertyFetch = $assign->var;
            if (!$this->nodeNameResolver->isName($propertyFetch->var, 'this')) {
                continue;
            }
            if (!$this->nodeNameResolver->isName($propertyFetch->name, $propertyName)) {
                continue;
            }
            return $this->nodeTypeResolver->getType($assign->expr);
        }
        return null;
    }
}
