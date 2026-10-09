<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\NodeFinder;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\ArrayDimFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\PropertyFetch;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
final readonly class ArrayDimFetchFinder
{
    public function __construct(private BetterNodeFinder $betterNodeFinder, private NodeNameResolver $nodeNameResolver)
    {
    }
    /**
     * @return Expr[]
     */
    public function findDimFetchAssignToVariableName(ClassMethod $classMethod, string $variableName): array
    {
        $assigns = $this->betterNodeFinder->findInstancesOfScoped((array) $classMethod->stmts, Assign::class);
        $exprs = [];
        foreach ($assigns as $assign) {
            if (!$assign->var instanceof ArrayDimFetch) {
                continue;
            }
            $arrayDimFetch = $assign->var;
            if (!$arrayDimFetch->var instanceof Variable) {
                continue;
            }
            if (!$this->nodeNameResolver->isName($arrayDimFetch->var, $variableName)) {
                continue;
            }
            $exprs[] = $assign->expr;
        }
        return $exprs;
    }
    /**
     * Look for bare assigns, $this->someProperty[] = ...
     * @return Expr[]
     */
    public function findDimFetchAssignToPropertyName(Class_ $class, string $variableName): array
    {
        $assigns = $this->betterNodeFinder->findInstancesOfScoped($class->getMethods(), Assign::class);
        $exprs = [];
        foreach ($assigns as $assign) {
            if (!$assign->var instanceof ArrayDimFetch) {
                continue;
            }
            $arrayDimFetch = $assign->var;
            if ($arrayDimFetch->dim instanceof Expr) {
                continue;
            }
            if (!$arrayDimFetch->var instanceof PropertyFetch) {
                continue;
            }
            $propertyFetch = $arrayDimFetch->var;
            if (!$this->nodeNameResolver->isName($propertyFetch->var, 'this')) {
                continue;
            }
            if (!$this->nodeNameResolver->isName($propertyFetch->name, $variableName)) {
                continue;
            }
            $exprs[] = $assign->expr;
        }
        return $exprs;
    }
    /**
     * Any write to the property other than a bare append $this->someProperty[] = ...,
     * i.e. a keyed dim assign $this->someProperty['key'] = ... or a direct $this->someProperty = ...
     */
    public function hasNonAppendAssignToPropertyName(Class_ $class, string $variableName): bool
    {
        $assigns = $this->betterNodeFinder->findInstancesOfScoped($class->getMethods(), Assign::class);
        foreach ($assigns as $assign) {
            if ($assign->var instanceof PropertyFetch && $this->isThisPropertyNamed($assign->var, $variableName)) {
                return \true;
            }
            if (!$assign->var instanceof ArrayDimFetch) {
                continue;
            }
            // bare append, already covered by findDimFetchAssignToPropertyName()
            if (!$assign->var->dim instanceof Expr) {
                continue;
            }
            if ($assign->var->var instanceof PropertyFetch && $this->isThisPropertyNamed($assign->var->var, $variableName)) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @return ArrayDimFetch[]
     */
    public function findByVariableName(Node $node, string $variableName): array
    {
        $dimFetches = $this->betterNodeFinder->findInstancesOfScoped([$node], ArrayDimFetch::class);
        return array_filter($dimFetches, function (ArrayDimFetch $arrayDimFetch) use ($variableName): bool {
            if (!$arrayDimFetch->var instanceof Variable) {
                return \false;
            }
            return $this->nodeNameResolver->isName($arrayDimFetch->var, $variableName);
        });
    }
    private function isThisPropertyNamed(PropertyFetch $propertyFetch, string $propertyName): bool
    {
        if (!$this->nodeNameResolver->isName($propertyFetch->var, 'this')) {
            return \false;
        }
        return $this->nodeNameResolver->isName($propertyFetch->name, $propertyName);
    }
    /**
     * @return ArrayDimFetch[]
     */
    public function findByDimName(ClassMethod $classMethod, string $dimName): array
    {
        $dimFetches = $this->betterNodeFinder->findInstancesOfScoped([$classMethod], ArrayDimFetch::class);
        return array_filter($dimFetches, function (ArrayDimFetch $arrayDimFetch) use ($dimName): bool {
            if (!$arrayDimFetch->dim instanceof Variable) {
                return \false;
            }
            return $this->nodeNameResolver->isName($arrayDimFetch->dim, $dimName);
        });
    }
}
